<?php

namespace Functional\Accounts\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Accounts\Models\ReservationPurchaseOrder;
use Functional\Billing\Actions\CreateBillingExport;
use Functional\Billing\Models\BillablePeriod;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Tests\Concerns\BuildsBillingFixtures;
use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurchaseOrderExportTest extends TestCase
{
    use BuildsBillingFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('billing-exports');
        CarbonImmutable::setTestNow('2026-11-15 10:30:00');
    }

    public function test_the_number_is_the_last_column_of_the_emergency_export(): void
    {
        $withNumber = $this->exportableRental('BC-2026-0412');
        $withoutNumber = $this->exportableRental(null);

        $billingExport = app(CreateBillingExport::class)->handle($this->employee());

        $lines = explode("\n", trim(Storage::disk('billing-exports')->get($billingExport->file_path) ?? ''));
        $this->assertStringEndsWith(';amount_excl_tax;source_ref;sale_date;purchase_order_number', $lines[0]);
        $this->assertStringStartsWith($withNumber->uuid, $lines[1]);
        $this->assertStringEndsWith(';BC-2026-0412', $lines[1]);
        $this->assertStringStartsWith($withoutNumber->uuid, $lines[2]);
        $this->assertStringEndsWith(';', $lines[2]);
    }

    public function test_the_numbers_cost_the_same_queries_whatever_the_number_of_lines(): void
    {
        $employee = $this->employee();
        $this->exportableRental('BC-1');
        $this->exportableRental('BC-2');
        $queriesForTwoLines = $this->countExportQueries($employee);

        foreach (range(1, 6) as $position) {
            $this->exportableRental("BC-{$position}");
        }

        $this->assertSame($queriesForTwoLines, $this->countExportQueries($employee));
    }

    private function exportableRental(?string $number): Transmission
    {
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');

        if ($number !== null) {
            ReservationPurchaseOrder::factory()->for($reservation)->create(['number' => $number]);
        }

        return Transmission::factory()->create([
            'billable_period_id' => BillablePeriod::factory()->create(['reservation_id' => $reservation->id])->id,
            'reservation_id' => $reservation->id,
            'created_at' => CarbonImmutable::now()->addSeconds(Transmission::query()->count()),
        ]);
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
