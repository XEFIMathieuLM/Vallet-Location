<?php

namespace Functional\Accounts\Exceptions;

use Functional\Booking\Models\Reservation;
use Functional\Fleet\Exceptions\RefusalException;

final class PurchaseOrderRefusedException extends RefusalException
{
    public static function notProfessional(Reservation $reservation): self
    {
        return new self(
            "Reservation {$reservation->id} is not for a professional customer and cannot have a purchase order.",
            'accounts::refusals.purchase_order_not_professional',
        );
    }
}
