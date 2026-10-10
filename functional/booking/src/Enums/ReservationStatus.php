<?php

namespace Functional\Booking\Enums;

enum ReservationStatus: string
{
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("booking::reservations.status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Confirmed => 'blue',
            self::InProgress => 'amber',
            self::Closed => 'zinc',
            self::Cancelled => 'zinc',
        };
    }
}
