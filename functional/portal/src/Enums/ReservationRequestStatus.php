<?php

namespace Functional\Portal\Enums;

use Functional\Fleet\Contracts\HasLabel;

enum ReservationRequestStatus: string implements HasLabel
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Refused = 'refused';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return __("portal::requests.statuses.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending, self::Cancelled => 'zinc',
            self::Confirmed => 'green',
            self::Refused => 'red',
            self::Expired => 'amber',
        };
    }
}
