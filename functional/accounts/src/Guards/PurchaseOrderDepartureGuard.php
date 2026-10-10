<?php

namespace Functional\Accounts\Guards;

use Functional\Accounts\Exceptions\MissingPurchaseOrderException;
use Functional\Accounts\Models\ReservationPurchaseOrder;
use Functional\Accounts\Support\KeyAccounts;
use Functional\Booking\Contracts\ReservationTransitionGuard;
use Functional\Booking\Models\Reservation;

final class PurchaseOrderDepartureGuard implements ReservationTransitionGuard
{
    public function __construct(private readonly KeyAccounts $keyAccounts) {}

    public function beforeDeparture(Reservation $reservation): void
    {
        if (! $this->keyAccounts->isKeyAccount($reservation->customer_id)) {
            return;
        }

        if (! ReservationPurchaseOrder::query()->where('reservation_id', $reservation->id)->exists()) {
            throw MissingPurchaseOrderException::for($reservation);
        }
    }

    public function beforeReturn(Reservation $reservation): void {}
}
