<?php

namespace Functional\Booking\Enums;

use Functional\Fleet\Contracts\HasLabel;

enum ReservationTransition: string implements HasLabel
{
    case Departure = 'departure';
    case Return = 'return';
    case Cancellation = 'cancellation';

    public function label(): string
    {
        return __("booking::reservations.transitions.{$this->value}");
    }
}
