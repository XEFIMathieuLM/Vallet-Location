<?php

namespace Functional\Inspection\Actions;

use Functional\Booking\Models\Reservation;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Inspection\Completeness\ViewCompleteness;
use Functional\Inspection\Events\PhotoChanged;
use Functional\Inspection\Exceptions\StepAlreadyValidatedException;
use Functional\Inspection\History\InspectionHistory;
use Functional\Inspection\History\InspectionHistoryEvent;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\PhotoSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeletePhoto
{
    public function __construct(
        private readonly ViewCompleteness $viewCompleteness,
        private readonly InspectionHistory $inspectionHistory,
    ) {}

    public function fromSession(PhotoSession $session, int $photoId): void
    {
        $photo = Photo::query()
            ->whereBelongsTo($session->reservation)
            ->where('step', $session->step)
            ->findOrFail($photoId);

        $this->handle($photo, $session->author);
    }

    public function handle(Photo $photo, (Model&AgencyMember)|null $author): void
    {
        $reservation = DB::transaction(function () use ($photo, $author): Reservation {
            $lockedReservation = Reservation::query()->lockForUpdate()->findOrFail($photo->reservation_id);

            if ($photo->step->isValidatedFor($lockedReservation)) {
                throw StepAlreadyValidatedException::for($lockedReservation, $photo->step);
            }

            $photo->delete();

            $this->inspectionHistory->record($lockedReservation, InspectionHistoryEvent::PhotoDeleted, $author, [
                'photo_id' => $photo->id,
                'view' => $photo->view->label,
                'step' => $photo->step->value,
            ]);

            return $lockedReservation;
        });

        PhotoChanged::dispatch(
            $reservation->id,
            $photo->step,
            $photo->reservation_view_id,
            $this->viewCompleteness->for($reservation)->missingCount($photo->step),
        );
    }
}
