<?php

namespace Functional\Billing\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Billing\Actions\CreateBillingExport;
use Functional\Billing\Actions\QueueSourceTransmission;
use Functional\Billing\Enums\BillableLineType;
use Functional\Billing\Enums\TransmissionFailureReason;
use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Exceptions\UnknownBillableSourceException;
use Functional\Billing\Extensions\BillableSources;
use Functional\Billing\Livewire\Transmissions;
use Functional\Billing\Models\CustomerBillingAccount;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Tests\Concerns\BuildsBillingFixtures;
use Functional\Billing\Tests\Doubles\TestBillableSource;
use Functional\Booking\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class BillableSourcesTest extends TestCase
{
    use BuildsBillingFixtures, RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-11-15 10:30:00');
        app(BillableSources::class)->register(TestBillableSource::class);
        $this->customer = Customer::factory()->create(['name' => 'Terrassements Martin']);
    }

    public function test_queuing_a_source_creates_one_transmission_without_reservation_whatever_the_number_of_calls(): void
    {
        Bus::fake();

        $transmission = $this->queue();
        $sameTransmission = $this->queue();

        $this->assertTrue($transmission->is($sameTransmission));
        $this->assertSame(1, Transmission::query()->count());
        $this->assertNull($transmission->reservation_id);
        $this->assertSame(BillableLineType::UsedMachineSale, $transmission->source_type);
        $this->assertSame($this->customer->id, $transmission->source_id);
        $this->assertSame(TransmissionStatus::Pending, $transmission->status);
    }

    public function test_a_source_transmission_is_sent_with_the_line_of_its_source_and_recorded_on_its_subject(): void
    {
        CustomerBillingAccount::factory()->create(['customer_id' => $this->customer->id, 'external_ref' => 'CLI-77']);
        $employee = $this->employee();
        Auth::login($employee);

        $transmission = $this->queue();

        $this->assertSame(TransmissionStatus::Sent, $transmission->fresh()?->status);
        $received = $this->fakeGateway()->received()[$transmission->uuid];
        $this->assertSame('used_machine_sale', $received['type']);
        $this->assertSame('CLI-77', $received['customer_ref']);
        $this->assertSame(1650000, $received['amount_excl_tax_cents']);
        $activity = Activity::query()->where('log_name', 'billing')->where('event', 'sent')->sole();
        $this->assertTrue($this->customer->is($activity->subject));
        $this->assertSame($employee->agencyId(), $activity->properties['author_agency_id']);
    }

    public function test_an_unknown_customer_fails_the_transmission_and_the_screen_shows_the_source(): void
    {
        $transmission = $this->queue();

        $this->assertSame(TransmissionFailureReason::CustomerUnknown, $transmission->fresh()?->failure_reason);
        Livewire::actingAs($this->employee())
            ->test(Transmissions::class)
            ->assertSee("Source de test {$this->customer->id}")
            ->assertSee('Terrassements Martin')
            ->assertSeeHtml("https://example.test/sources/{$this->customer->id}")
            ->set("customerRefs.{$this->customer->id}", 'CLI-78')
            ->call('saveCustomerRef', $this->customer->id)
            ->call('retry', $transmission->id);

        $this->assertSame(TransmissionStatus::Sent, $transmission->fresh()?->status);
    }

    public function test_a_failed_source_transmission_goes_into_the_emergency_export_with_its_own_columns(): void
    {
        Storage::fake('billing-exports');
        $transmission = $this->queue();

        $billingExport = app(CreateBillingExport::class)->handle($this->employee());

        $this->assertSame(TransmissionStatus::Exported, $transmission->fresh()?->status);
        $lines = explode("\n", trim(Storage::disk('billing-exports')->get($billingExport->file_path) ?? ''));
        $this->assertStringContainsString('source_ref;sale_date', $lines[0]);
        $this->assertStringStartsWith("{$transmission->uuid};used_machine_sale;", $lines[1]);
        $this->assertStringEndsWith(";16500,00;TEST-{$this->customer->id};2026-11-15", $lines[1]);
    }

    public function test_an_unregistered_source_type_is_refused(): void
    {
        $this->expectException(UnknownBillableSourceException::class);

        (new BillableSources)->for(BillableLineType::UsedMachineSale);
    }

    private function queue(): Transmission
    {
        $transmission = app(QueueSourceTransmission::class)->handle(BillableLineType::UsedMachineSale, $this->customer->id);

        return $transmission->refresh();
    }
}
