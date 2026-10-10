<?php

namespace Functional\Inspection\Enums;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;

enum InspectionStep: string
{
    case Departure = 'departure';
    case Return = 'return';

    public function label(): string
    {
        return __("inspection::photos.step.{$this->value}");
    }

    public function isOpenFor(Reservation $reservation): bool
    {
        return match ($this) {
            self::Departure => $reservation->status === ReservationStatus::Confirmed
                && CarbonImmutable::today()->greaterThanOrEqualTo($reservation->start_date),
            self::Return => $reservation->status === ReservationStatus::InProgress,
        };
    }

    public function isValidatedFor(Reservation $reservation): bool
    {
        return match ($this) {
            self::Departure => $reservation->status !== ReservationStatus::Confirmed,
            self::Return => $reservation->status === ReservationStatus::Closed,
        };
    }
}
