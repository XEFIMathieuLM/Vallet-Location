<?php

namespace Functional\Inspection\Actions;

use Functional\Inspection\Events\PhotoChanged;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class StorePhoto
{
    public function __construct(
        private readonly FindActivePhotoSession $findActivePhotoSession,
        private readonly MissingViews $missingViews,
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
