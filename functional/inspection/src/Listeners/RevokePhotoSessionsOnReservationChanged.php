<?php

namespace Functional\Inspection\Listeners;

use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Events\ReservationChanged;
use Functional\Inspection\Actions\RevokePhotoSessions;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Enums\RevocationReason;

class RevokePhotoSessionsOnReservationChanged
{
    public function __construct(private readonly RevokePhotoSessions $revokePhotoSessions) {}

    public function handle(ReservationChanged $event): void
    {
        $reservation = $event->reservation;

        match ($reservation->status) {
            ReservationStatus::InProgress => $this->revokePhotoSessions->handle($reservation, [InspectionStep::Departure], RevocationReason::StepValidated),
            ReservationStatus::Closed => $this->revokePhotoSessions->handle($reservation, InspectionStep::cases(), RevocationReason::StepValidated),
            ReservationStatus::Cancelled => $this->revokePhotoSessions->handle($reservation, InspectionStep::cases(), RevocationReason::ReservationCancelled),
            ReservationStatus::Confirmed => null,
        };
    }
}
