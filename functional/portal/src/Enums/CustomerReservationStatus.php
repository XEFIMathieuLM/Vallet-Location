<?php

namespace Functional\Portal\Enums;

use Functional\Booking\Enums\ReservationStatus;
use Functional\Fleet\Contracts\HasLabel;

enum CustomerReservationStatus: string implements HasLabel
{
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Finished = 'finished';
    case Cancelled = 'cancelled';

    public static function fromReservationStatus(ReservationStatus $reservationStatus): self
    {
        return match ($reservationStatus) {
            ReservationStatus::Confirmed => self::Confirmed,
            ReservationStatus::InProgress => self::InProgress,
            ReservationStatus::Closed => self::Finished,
            ReservationStatus::Cancelled => self::Cancelled,
        };
    }

    public function label(): string
    {
        return __("portal::reservations.statuses.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Confirmed => 'blue',
            self::InProgress => 'green',
            self::Finished, self::Cancelled => 'zinc',
        };
    }
}
