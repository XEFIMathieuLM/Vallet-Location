<?php

namespace Functional\Inspection\Actions;

use Functional\Inspection\Events\PhotoChanged;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\ReservationView;
use Functional\Inspection\Support\InspectionHistory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class StorePhoto
{
    public function __construct(
        private readonly FindActivePhotoSession $findActivePhotoSession,
        private readonly MissingViews $missingViews,
        private readonly InspectionHistory $inspectionHistory,
    ) {}

    public function handle(string $token, int $reservationViewId, UploadedFile $file): Photo
    {
        $session = $this->findActivePhotoSession->handle($token);

        $view = ReservationView::query()
            ->where('reservation_id', $session->reservation_id)
            ->findOrFail($reservationViewId);

        $photo = DB::transaction(function () use ($session, $view, $file): Photo {
            $photo = Photo::query()->create([
                'reservation_id' => $session->reservation_id,
                'reservation_view_id' => $view->id,
                'step' => $session->step,
                'photo_session_id' => $session->id,
            ]);

            $photo->addMedia($file)->toMediaCollection(Photo::COLLECTION);

            $this->inspectionHistory->record($session->reservation, 'photo.received', $session->author, [
                'photo_id' => $photo->id,
                'view' => $view->label,
                'step' => $session->step->value,
            ]);

            return $photo;
        });

        PhotoChanged::dispatch(
            $session->reservation_id,
            $session->step,
            $view->id,
            $this->missingViews->for($session->reservation, $session->step)->count(),
        );

        return $photo;
    }
}
