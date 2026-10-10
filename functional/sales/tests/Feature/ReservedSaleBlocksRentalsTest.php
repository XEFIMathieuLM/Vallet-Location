<?php

namespace Functional\Sales\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Actions\CreateReservation;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Booking\Queries\AvailableMachinesQuery;
use Functional\Fleet\Models\Machine;
use Functional\Sales\Actions\ChangePlannedHandoverDate;
use Functional\Sales\Exceptions\HandoverConflictsWithReservationException;
use Functional\Sales\Exceptions\MachineReservedForSaleException;
use Functional\Sales\Tests\Concerns\BuildsSalesFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservedSaleBlocksRentalsTest extends TestCase
{
    use BuildsSalesFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-02 10:00'));
    }

    public function test_a_rental_ending_on_or_after_the_handover_date_is_refused_and_hidden_from_the_search(): void
    {
        $sale = $this->reservedSale('2026-11-20');

        $this->assertRefused(MachineReservedForSaleException::class, '20/11/2026', fn () => $this->reserve($sale->machine, '2026-11-18', '2026-11-22'));
        $this->assertRefused(MachineReservedForSaleException::class, '20/11/2026', fn () => $this->reserve($sale->machine, '2026-11-20', '2026-11-20'));
        $this->assertSame(0, Reservation::query()->count());
        $this->assertFalse(app(AvailableMachinesQuery::class)->get(CarbonImmutable::parse('2026-11-18'), CarbonImmutable::parse('2026-11-22'))->contains($sale->machine));
    }

    public function test_a_rental_ending_before_the_handover_date_is_accepted(): void
    {
        $sale = $this->reservedSale('2026-11-20');

        $this->assertSame(ReservationStatus::Confirmed, $this->reserve($sale->machine, '2026-11-10', '2026-11-14')->status);
        $this->assertTrue(app(AvailableMachinesQuery::class)->get(CarbonImmutable::parse('2026-11-15'), CarbonImmutable::parse('2026-11-19'))->contains($sale->machine));
    }

    public function test_the_planned_handover_date_moves_only_where_no_rental_ends_on_or_after_it(): void
    {
        $sale = $this->reservedSale('2026-11-20');
        $this->confirmedReservation($sale->machine, '2026-11-10', '2026-11-14');

        $this->assertRefused(HandoverConflictsWithReservationException::class, '14/11/2026', fn () => app(ChangePlannedHandoverDate::class)->handle($this->employee(), $sale, CarbonImmutable::parse('2026-11-12')));
        $this->assertSame('2026-11-20', $sale->fresh()?->planned_handover_date?->toDateString());

        app(ChangePlannedHandoverDate::class)->handle($this->employee(), $sale, CarbonImmutable::parse('2026-11-15'));
        $this->assertSame('2026-11-15', $sale->fresh()?->planned_handover_date?->toDateString());
        $this->assertRefused(MachineReservedForSaleException::class, '15/11/2026', fn () => $this->reserve($sale->machine, '2026-11-16', '2026-11-17'));
    }

    private function reserve(Machine $machine, string $startDate, string $endDate): Reservation
    {
        return app(CreateReservation::class)->handle($this->employee(), $machine, Customer::factory()->create(), CarbonImmutable::parse($startDate), CarbonImmutable::parse($endDate));
    }
}
