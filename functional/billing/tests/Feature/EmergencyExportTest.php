<?php

namespace Functional\Billing\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Billing\Actions\CreateBillingExport;
use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Exceptions\NothingToExportException;
use Functional\Billing\Livewire\Exports;
use Functional\Billing\Models\BillablePeriod;
use Functional\Billing\Models\BillingExport;
use Functional\Billing\Models\DamageSettlement;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Money\Money;
use Functional\Billing\Tests\Concerns\BuildsBillingFixtures;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EmergencyExportTest extends TestCase
{
    use AssertsRefusals, BuildsBillingFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('billing-exports');
        CarbonImmutable::setTestNow('2026-11-15 10:30:00');
    }

    public function test_the_export_takes_every_pending_and_failed_transmission_and_marks_them_exported(): void
    {
        $employee = $this->employee();
        $pending = Transmission::factory()->count(2)->create();
        $failed = Transmission::factory()->failed()->create();
        $sent = Transmission::factory()->sent()->create();

        $billingExport = app(CreateBillingExport::class)->handle($employee);

        $this->assertSame(3, $billingExport->line_count);
        $this->assertSame('export-facturation-20261115-103000.csv', $billingExport->file_path);
        foreach ([...$pending, $failed] as $transmission) {
            $transmission->refresh();
            $this->assertSame(TransmissionStatus::Exported, $transmission->status);
            $this->assertSame($billingExport->id, $transmission->billing_export_id);
        }
        $this->assertSame(TransmissionStatus::Sent, $sent->refresh()->status);
        $this->assertStringNotContainsString($sent->uuid, Storage::disk('billing-exports')->get($billingExport->file_path) ?? '');
    }

    public function test_exported_transmissions_are_never_sent_again_nor_exported_twice(): void
    {
        $employee = $this->employee();
        Transmission::factory()->create();
        app(CreateBillingExport::class)->handle($employee);

        $this->artisan('billing:reconcile')->assertSuccessful();

        $this->assertSame([], $this->fakeGateway()->received());
        $this->assertRefused(NothingToExportException::class, 'il n\'y a rien à exporter', fn () => app(CreateBillingExport::class)->handle($employee));
    }

    public function test_the_file_follows_the_export_format(): void
    {
        $employee = $this->employee();
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');
        $settlement = DamageSettlement::factory()->billed(Money::fromStored(45000), 'Remplacement capot')->create(['damage_id' => $this->unresolvedDamage($reservation)->id]);
        $transmission = Transmission::factory()->create(['billable_period_id' => null, 'damage_settlement_id' => $settlement->id, 'reservation_id' => $reservation->id]);

        $billingExport = app(CreateBillingExport::class)->handle($employee);

        $content = Storage::disk('billing-exports')->get($billingExport->file_path) ?? '';
        $lines = explode("\n", trim($content));
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertSame(
            'idempotency_key;type;customer_ref;reservation_ref;machine_reference;machine_category;home_agency;booking_agency;period_start;period_end;period_kind;days;damage_view;damage_comment;label;amount_excl_tax;source_ref;sale_date;purchase_order_number',
            substr($lines[0], 3),
        );
        $this->assertStringStartsWith("{$transmission->uuid};damage;", $lines[1]);
        $this->assertStringEndsWith(';"Remplacement capot";450,00;;;', $lines[1]);
    }

    public function test_the_screen_produces_an_export_and_lists_past_exports_for_download(): void
    {
        $this->actingAs($this->employee());
        Transmission::factory()->count(2)->create();

        Livewire::test(Exports::class)
            ->call('export')
            ->assertHasNoErrors()
            ->assertSee('15/11/2026 10:30')
            ->assertSee('2 lignes');

        $billingExport = BillingExport::query()->sole();
        $this->get(route('billing.exports.download', $billingExport))->assertOk()->assertDownload($billingExport->file_path);
    }

    public function test_an_export_with_nothing_pending_is_refused_on_screen(): void
    {
        $this->actingAs($this->employee());

        Livewire::test(Exports::class)
            ->call('export')
            ->assertHasErrors('refusal');

        $this->assertSame(0, BillingExport::query()->count());
    }

    public function test_the_number_of_queries_of_an_export_does_not_grow_with_its_lines(): void
    {
        $employee = $this->employee();
        $this->exportableRentalsWithDamage(2);
        $queriesForTwoRentals = $this->countExportQueries($employee);
        $this->exportableRentalsWithDamage(6);

        $this->assertSame($queriesForTwoRentals, $this->countExportQueries($employee));
    }

    public function test_a_file_that_cannot_be_written_changes_no_transmission_and_records_no_export(): void
    {
        $employee = $this->employee();
        $transmissions = Transmission::factory()->count(2)->create();
        config(['filesystems.disks.billing-exports' => ['driver' => 'local', 'root' => '/proc/billing-exports', 'throw' => true]]);
        Storage::forgetDisk('billing-exports');

        $this->assertThrows(fn () => app(CreateBillingExport::class)->handle($employee));

        $this->assertSame(0, BillingExport::query()->count());
        $transmissions->each(fn (Transmission $transmission) => $this->assertSame(TransmissionStatus::Pending, $transmission->refresh()->status));
    }

    private function exportableRentalsWithDamage(int $count): void
    {
        foreach (range(1, $count) as $position) {
            $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');
            Transmission::factory()->create(['billable_period_id' => BillablePeriod::factory()->create(['reservation_id' => $reservation->id])->id, 'reservation_id' => $reservation->id]);
            $settlement = DamageSettlement::factory()->billed(Money::fromStored(1000 * $position), 'Réparation')->create(['damage_id' => $this->unresolvedDamage($reservation)->id]);
            Transmission::factory()->create(['billable_period_id' => null, 'damage_settlement_id' => $settlement->id, 'reservation_id' => $reservation->id]);
        }
    }

    private function countExportQueries(Model&AgencyMember $employee): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        app(CreateBillingExport::class)->handle($employee);
        DB::disableQueryLog();

        return count(array_filter(
            array_column(DB::getQueryLog(), 'query'),
            fn (string $query): bool => str_starts_with(strtolower(ltrim($query)), 'select'),
        ));
    }
}
