<?php

namespace Functional\Accounts\Tests\Feature;

use Functional\Accounts\Exceptions\MissingPurchaseOrderException;
use Functional\Accounts\Models\KeyAccount;
use Functional\Accounts\Models\ReservationPurchaseOrder;
use Functional\Accounts\Tests\Concerns\BuildsKeyAccountReservations;
use Functional\Booking\Actions\DepartReservation;
use Functional\Booking\Actions\ReturnReservation;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Enums\ReturnCondition;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderDepartureGuardTest extends TestCase
{
    use AssertsRefusals, BuildsKeyAccountReservations, CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->onlyThePurchaseOrderGuard();
    }

    public function test_the_departure_of_a_key_account_without_purchase_order_is_refused(): void
    {
        $reservation = $this->keyAccountReservation();

        $this->assertRefused(MissingPurchaseOrderException::class, 'bon de commande', fn () => app(DepartReservation::class)->handle($reservation));
        $this->assertSame(ReservationStatus::Confirmed, $reservation->refresh()->status);
    }

    public function test_the_departure_of_a_key_account_with_purchase_order_is_accepted(): void
    {
        $reservation = $this->keyAccountReservation();
        ReservationPurchaseOrder::factory()->for($reservation)->create();

        app(DepartReservation::class)->handle($reservation);

        $this->assertSame(ReservationStatus::InProgress, $reservation->refresh()->status);
    }

    public function test_a_key_account_rental_already_in_progress_without_purchase_order_can_be_returned(): void
    {
        $reservation = Reservation::factory()
            ->for(KeyAccount::factory()->create()->customer)
            ->for(Machine::factory()->withStatus(MachineStatus::RentedOut))
            ->ongoing()
            ->create();

        app(ReturnReservation::class)->handle($reservation, ReturnCondition::cases()[0]);

        $this->assertSame(ReservationStatus::Closed, $reservation->refresh()->status);
    }
}
