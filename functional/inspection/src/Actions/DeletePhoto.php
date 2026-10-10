<?php

namespace Functional\Inspection\Actions;

use Functional\Inspection\Events\PhotoChanged;
use Functional\Inspection\Exceptions\StepAlreadyValidatedException;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\PhotoSession;

class DeletePhoto
{
    public function __construct(private readonly MissingViews $missingViews) {}

    public function fromSession(PhotoSession $session, int $photoId): void
    {
        $photo = Photo::query()
            ->where('reservation_id', $session->reservation_id)
            ->where('step', $session->step)
            ->findOrFail($photoId);

        $this->handle($photo);
    }

    public function handle(Photo $photo): void
    {
        $reservation = $photo->reservation;

        if ($photo->step->isValidatedFor($reservation)) {
            throw StepAlreadyValidatedException::for($photo->step);
        }

        $photo->delete();

        PhotoChanged::dispatch(
            $reservation->id,
            $photo->step,
            $photo->reservation_view_id,
            $this->missingViews->for($reservation, $photo->step)->count(),
        );
    }
}
