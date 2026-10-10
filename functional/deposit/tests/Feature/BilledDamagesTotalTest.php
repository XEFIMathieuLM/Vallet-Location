<?php

namespace Functional\Deposit\Tests\Feature;

use Functional\Booking\Models\Reservation;
use Functional\Deposit\Queries\BilledDamagesTotal;
use Functional\Deposit\Tests\Concerns\BuildsDepositFixtures;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BilledDamagesTotalTest extends TestCase
{
    use BuildsDepositFixtures, RefreshDatabase;

    public function test_only_the_billed_damages_of_the_reservation_are_summed(): void
    {
        $reservation = $this->reservationWithView();
        $otherReservation = $this->reservationWithView();
        $this->billedDamageOn($reservation, 45000);
        $this->billedDamageOn($reservation, 30000);
        $this->waivedDamageOn($reservation);
        $this->billedDamageOn($otherReservation, 99900);

        $this->assertSame(75000, app(BilledDamagesTotal::class)->for($reservation)->minorUnits);
    }

    public function test_a_reservation_without_settlement_totals_zero(): void
    {
        $this->assertSame(0, app(BilledDamagesTotal::class)->for($this->reservationWithView())->minorUnits);
    }

    private function reservationWithView(): Reservation
    {
        $reservation = Reservation::factory()->create();
        ReservationView::factory()->for($reservation)->create();

        return $reservation;
    }
}
