<?php

namespace Functional\Accounts\Exceptions;

use Functional\Booking\Models\Reservation;
use Functional\Fleet\Exceptions\RefusalException;

final class MissingPurchaseOrderException extends RefusalException
{
    public static function for(Reservation $reservation): self
    {
        return new self(
            "Departure of reservation {$reservation->id} refused: the key account purchase order number is missing.",
            'accounts::refusals.purchase_order_missing',
        );
    }
}
