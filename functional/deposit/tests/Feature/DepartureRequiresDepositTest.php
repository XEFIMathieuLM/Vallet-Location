<?php

namespace Functional\Deposit\Tests\Feature;

use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Deposit\Exceptions\DepositRefusedException;
use Functional\Deposit\Tests\Concerns\BuildsDepositFixtures;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartureRequiresDepositTest extends TestCase
{
    use AssertsRefusals, BuildsDepositFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPhotoStorage();
        $this->actingAs($this->seededEmployee());
    }

    public function test_an_individual_cannot_leave_without_a_collected_deposit(): void
    {
        $reservation = $this->confirmedReservationFor(Customer::factory()->individual()->create());

        $this->assertRefused(DepositRefusedException::class, '1500,00 €', fn () => $this->depart($reservation));

        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()?->status);
    }

    public function test_an_individual_leaves_once_the_deposit_is_collected(): void
    {
        $reservation = $this->confirmedReservationFor(Customer::factory()->individual()->create());
        $this->collect($reservation, $this->employee());

        $this->depart($reservation);

        $this->assertSame(ReservationStatus::InProgress, $reservation->fresh()?->status);
    }

    public function test_a_professional_leaves_without_deposit(): void
    {
        $reservation = $this->confirmedReservationFor(Customer::factory()->professional()->create());

        $this->depart($reservation);

        $this->assertSame(ReservationStatus::InProgress, $reservation->fresh()?->status);
    }

    public function test_an_untyped_customer_cannot_leave_until_qualified(): void
    {
        $reservation = $this->confirmedReservationFor(Customer::factory()->untyped()->create());

        $this->assertRefused(DepositRefusedException::class, 'type de client', fn () => $this->depart($reservation));
    }
}
