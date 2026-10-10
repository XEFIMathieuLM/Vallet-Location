<?php

namespace Functional\Deposit\Tests\Feature;

use Functional\Billing\Money\Money;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Deposit\Enums\DepositSituationKind;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Models\Deposit;
use Functional\Deposit\Queries\DepositSituation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DepositSituationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_professional_customer_needs_no_deposit(): void
    {
        $situation = app(DepositSituation::class)->for($this->reservationOf(Customer::factory()->professional()->create()));

        $this->assertSame(DepositSituationKind::NotRequired, $situation->kind);
        $this->assertTrue($situation->isDepartureReady());
    }

    public function test_an_untyped_customer_of_a_confirmed_reservation_must_be_qualified(): void
    {
        $situation = app(DepositSituation::class)->for($this->reservationOf(Customer::factory()->untyped()->create()));

        $this->assertSame(DepositSituationKind::CustomerTypeMissing, $situation->kind);
        $this->assertFalse($situation->isDepartureReady());
    }

    public function test_an_individual_without_deposit_on_a_confirmed_reservation_has_a_deposit_to_collect(): void
    {
        $situation = app(DepositSituation::class)->for($this->reservationOf(Customer::factory()->individual()->create()));

        $this->assertSame(DepositSituationKind::ToCollect, $situation->kind);
        $this->assertTrue($situation->expectedAmount?->equals(Money::fromStored(150000)));
        $this->assertFalse($situation->isDepartureReady());
    }

    /**
     * @return iterable<string, array{ReservationStatus}>
     */
    public static function reservationsNotConfirmed(): iterable
    {
        yield 'in progress' => [ReservationStatus::InProgress];
        yield 'closed' => [ReservationStatus::Closed];
        yield 'cancelled' => [ReservationStatus::Cancelled];
    }

    #[DataProvider('reservationsNotConfirmed')]
    public function test_a_reservation_past_confirmation_without_deposit_is_not_tracked(ReservationStatus $status): void
    {
        $reservation = Reservation::factory()->for(Customer::factory()->individual())->withStatus($status)->create();

        $situation = app(DepositSituation::class)->for($reservation);

        $this->assertSame(DepositSituationKind::NotTracked, $situation->kind);
        $this->assertTrue($situation->isDepartureReady());
    }

    public function test_an_existing_deposit_is_tracked_with_its_status(): void
    {
        $reservation = $this->reservationOf(Customer::factory()->individual()->create());
        Deposit::factory()->for($reservation)->withStatus(DepositStatus::Collected)->create();

        $situation = app(DepositSituation::class)->for($reservation);

        $this->assertSame(DepositSituationKind::Tracked, $situation->kind);
        $this->assertSame(DepositStatus::Collected, $situation->deposit?->status);
        $this->assertTrue($situation->isDepartureReady());
    }

    private function reservationOf(Customer $customer): Reservation
    {
        return Reservation::factory()->for($customer)->create();
    }
}
