<?php

namespace Functional\Inspection\Enums;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Enums\ReservationTransition;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Contracts\HasLabel;

enum InspectionStep: string implements HasLabel
{
    case Departure = 'departure';
    case Return = 'return';

    public function label(): string
    {
        return __("inspection::photos.step.{$this->value}");
    }

    public function transition(): ReservationTransition
    {
        return match ($this) {
            self::Departure => ReservationTransition::Departure,
            self::Return => ReservationTransition::Return,
        };
    }

    public function isOpenFor(Reservation $reservation): bool
    {
        return $this->opensWhen($reservation->status, $reservation->start_date, CarbonImmutable::today());
    }

    public function isValidatedFor(Reservation $reservation): bool
    {
        return $this->isValidatedOnceReservationIs($reservation->status);
    }

    public function opensWhen(ReservationStatus $status, CarbonImmutable $startDate, CarbonImmutable $today): bool
    {
        return match ($this) {
            self::Departure => $status === ReservationStatus::Confirmed && $today->greaterThanOrEqualTo($startDate),
            self::Return => $status === ReservationStatus::InProgress,
        };
    }

    public function isValidatedOnceReservationIs(ReservationStatus $status): bool
    {
        return match ($this) {
            self::Departure => $status !== ReservationStatus::Confirmed,
            self::Return => $status === ReservationStatus::Closed,
        };
    }
}
