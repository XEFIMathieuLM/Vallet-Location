<?php

namespace Functional\Sales\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Billing\Actions\CreateBillingExport;
use Functional\Billing\Enums\FakeGatewayMode;
use Functional\Billing\Enums\TransmissionFailureReason;
use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Livewire\Transmissions;
use Functional\Billing\Models\CustomerBillingAccount;
use Functional\Billing\Models\Transmission;
use Functional\Sales\Actions\HandOverSale;
use Functional\Sales\Enums\SaleStatus;
use Functional\Sales\Models\Sale;
use Functional\Sales\Tests\Concerns\BuildsSalesFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SaleTransmissionReliabilityTest extends TestCase
{
    use BuildsSalesFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-20 10:00'));
    }

    public function test_with_the_billing_software_unreachable_the_handover_is_recorded_and_the_sale_waits(): void
    {
        $this->fakeGateway()->switchTo(FakeGatewayMode::Unreachable);
        $sale = $this->soldWithBillingAccount();

        $this->assertSame(SaleStatus::Sold, $sale->fresh()?->status);
        $this->assertSame(TransmissionStatus::Pending, $this->transmissionOf($sale)->status);
    }

    public function test_once_the_billing_software_is_back_the_sale_is_transmitted_without_any_action_and_only_once(): void
    {
        $this->fakeGateway()->switchTo(FakeGatewayMode::Unreachable);
        $sale = $this->soldWithBillingAccount();
        $this->fakeGateway()->switchTo(FakeGatewayMode::Accept);
        $this->travel(2)->hours();

        $this->artisan('billing:reconcile')->assertSuccessful();
        $this->artisan('billing:reconcile')->assertSuccessful();

        $transmission = $this->transmissionOf($sale);
        $this->assertSame(TransmissionStatus::Sent, $transmission->status);
        $this->assertCount(1, array_filter(array_keys($this->fakeGateway()->received()), fn (string $key): bool => $key === $transmission->uuid));
    }

    public function test_a_buyer_unknown_to_the_billing_software_puts_the_sale_in_the_list_to_handle(): void
    {
        $sale = Sale::factory()->reserved(CarbonImmutable::parse('2026-11-20'))->create()->refresh();
        $sale->buyer?->update(['name' => 'Loc\'TP Normandie']);
        app(HandOverSale::class)->handle($this->employee(), $sale);

        $this->assertSame(TransmissionFailureReason::CustomerUnknown, $this->transmissionOf($sale)->failure_reason);
        Livewire::actingAs($this->employee())->test(Transmissions::class)
            ->assertSee("Vente {$sale->machine->reference}")
            ->assertSee('Loc\'TP Normandie')
            ->assertSeeHtml(route('sales.show', $sale))
            ->assertSee('Vente d\'occasion');
    }

    public function test_retrying_a_sent_sale_never_bills_it_twice(): void
    {
        $sale = $this->soldWithBillingAccount();
        $transmission = $this->transmissionOf($sale);

        $this->artisan('billing:reconcile')->assertSuccessful();

        $this->assertSame(TransmissionStatus::Sent, $transmission->fresh()?->status);
        $this->assertSame(1, $transmission->fresh()?->attempts);
    }

    public function test_a_failed_sale_goes_into_the_emergency_export_and_is_then_exported(): void
    {
        Storage::fake('billing-exports');
        $this->fakeGateway()->switchTo(FakeGatewayMode::Unreachable);
        $sale = $this->soldWithBillingAccount();

        $billingExport = app(CreateBillingExport::class)->handle($this->employee());

        $this->assertSame(TransmissionStatus::Exported, $this->transmissionOf($sale)->status);
        $this->assertStringContainsString("SALE-{$sale->id}", Storage::disk('billing-exports')->get($billingExport->file_path) ?? '');
    }

    private function soldWithBillingAccount(): Sale
    {
        $sale = $this->reservedSale('2026-11-20');
        CustomerBillingAccount::factory()->create(['customer_id' => $sale->buyer_id]);

        return app(HandOverSale::class)->handle($this->employee(), $sale);
    }

    private function transmissionOf(Sale $sale): Transmission
    {
        return Transmission::query()->where('source_id', $sale->id)->sole();
    }
}
