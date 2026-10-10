<?php

namespace Functional\Accounts\Exceptions;

use Functional\Booking\Models\Reservation;
use Functional\Fleet\Exceptions\RefusalException;

final class PurchaseOrderFrozenException extends RefusalException
{
    public static function for(Reservation $reservation): self
    {
        return new self(
            "The purchase order of reservation {$reservation->id} is frozen because the reservation is {$reservation->status->value}.",
            'accounts::refusals.purchase_order_frozen',
        );
    }
}
