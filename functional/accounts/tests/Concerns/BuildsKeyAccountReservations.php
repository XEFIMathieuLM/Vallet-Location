<?php

namespace Functional\Accounts\Tests\Concerns;

use Carbon\CarbonImmutable;
use Functional\Accounts\Guards\PurchaseOrderDepartureGuard;
use Functional\Accounts\Models\KeyAccount;
use Functional\Booking\Extensions\ReservationTransitionGuards;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;

trait BuildsKeyAccountReservations
{
    protected function keyAccountReservation(): Reservation
    {
        return $this->reservationStartingToday(KeyAccount::factory()->create()->customer);
    }

    protected function reservationStartingToday(Customer $customer): Reservation
    {
        return Reservation::factory()
            ->for($customer)
            ->between(CarbonImmutable::today(), CarbonImmutable::today()->addDays(4))
            ->create();
    }

    protected function onlyThePurchaseOrderGuard(): void
    {
        $guards = new ReservationTransitionGuards;
        $guards->register(PurchaseOrderDepartureGuard::class);
        $this->app->instance(ReservationTransitionGuards::class, $guards);
    }
}
