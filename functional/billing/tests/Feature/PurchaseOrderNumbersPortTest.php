<?php

namespace Functional\Billing\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Billing\Actions\CreateBillingExport;
use Functional\Billing\Actions\MakeBillableLine;
use Functional\Billing\Contracts\PurchaseOrderNumbers;
use Functional\Billing\Lines\NullPurchaseOrderNumbers;
use Functional\Billing\Models\BillablePeriod;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Tests\Concerns\BuildsBillingFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurchaseOrderNumbersPortTest extends TestCase
{
    use BuildsBillingFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(PurchaseOrderNumbers::class, NullPurchaseOrderNumbers::class);
        Storage::fake('billing-exports');
        CarbonImmutable::setTestNow('2026-11-15 10:30:00');
    }

    public function test_without_purchase_order_source_the_line_has_an_empty_number(): void
    {
        $line = app(MakeBillableLine::class)->handle($this->pendingRentalTransmission())->toArray();

        $this->assertArrayHasKey('purchase_order_number', $line);
        $this->assertNull($line['purchase_order_number']);
    }

    public function test_without_purchase_order_source_the_export_column_is_empty(): void
    {
        $this->pendingRentalTransmission();

        $billingExport = app(CreateBillingExport::class)->handle($this->employee());

        $lines = explode("\n", trim(Storage::disk('billing-exports')->get($billingExport->file_path) ?? ''));
        $this->assertStringEndsWith(';purchase_order_number', $lines[0]);
        $this->assertStringEndsWith(';', $lines[1]);
    }

    private function pendingRentalTransmission(): Transmission
    {
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');

        return Transmission::factory()->create([
            'billable_period_id' => BillablePeriod::factory()->create(['reservation_id' => $reservation->id])->id,
            'reservation_id' => $reservation->id,
        ]);
    }
}
