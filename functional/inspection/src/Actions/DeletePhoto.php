<?php

namespace Functional\Inspection\Actions;

use App\Models\User;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Events\PhotoChanged;
use Functional\Inspection\Exceptions\StepAlreadyValidatedException;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\PhotoSession;
use Functional\Inspection\Support\InspectionHistory;
use Illuminate\Support\Facades\DB;

class DeletePhoto
{
    public function __construct(
        private readonly MissingViews $missingViews,
        private readonly InspectionHistory $inspectionHistory,
    ) {}

    public function fromSession(PhotoSession $session, int $photoId): void
    {
        $photo = Photo::query()
            ->where('reservation_id', $session->reservation_id)
            ->where('step', $session->step)
            ->findOrFail($photoId);

        $this->handle($photo, $session->author);
    }

    public function handle(Photo $photo, ?User $author): void
    {
        $reservation = DB::transaction(function () use ($photo, $author): Reservation {
            $lockedReservation = Reservation::query()->lockForUpdate()->findOrFail($photo->reservation_id);

            if ($photo->step->isValidatedFor($lockedReservation)) {
                throw StepAlreadyValidatedException::for($photo->step);
            }

            $photo->delete();

            $this->inspectionHistory->record($lockedReservation, 'photo.deleted', $author, [
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
            $this->missingViews->for($reservation, $photo->step)->count(),
        );
    }
}
