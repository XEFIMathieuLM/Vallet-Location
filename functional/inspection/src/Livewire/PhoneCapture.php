<?php

namespace Functional\Inspection\Livewire;

use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Inspection\Actions\DeletePhoto;
use Functional\Inspection\Actions\FindActivePhotoSession;
use Functional\Inspection\Actions\PhotoTemporaryUrl;
use Functional\Inspection\Actions\StorePhoto;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\PhotoSession;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('inspection::layouts.phone')]
class PhoneCapture extends Component
{
    use DisplaysRefusals, WithFileUploads;

    #[Locked]
    public string $token = '';

    /**
     * @var array<int, TemporaryUploadedFile|null>
     */
    public array $uploads = [];

    public function mount(string $token): void
    {
        $this->token = $token;
    }

    public function updatedUploads(mixed $file, string $reservationViewId): void
    {
        $this->validate([
            "uploads.{$reservationViewId}" => ['required', 'image', 'mimes:'.implode(',', config()->array('inspection.allowed_mimes')), 'max:'.config()->integer('inspection.max_photo_kilobytes')],
        ]);

        /** @var TemporaryUploadedFile $upload */
        $upload = $this->uploads[(int) $reservationViewId];

        app(StorePhoto::class)->handle($this->token, (int) $reservationViewId, $upload);

        unset($this->uploads[(int) $reservationViewId]);
    }

    public function deletePhoto(int $photoId): void
    {
        $session = app(FindActivePhotoSession::class)->handle($this->token);

        app(DeletePhoto::class)->fromSession($session, $photoId);
    }

    public function render(): View
    {
        $session = app(FindActivePhotoSession::class)->find($this->token);

        if ($session === null) {
            return view('inspection::phone.expired');
        }

        $views = $this->viewsWithPhotos($session);
        $photoTemporaryUrl = app(PhotoTemporaryUrl::class);

        return view('inspection::livewire.phone-capture', [
            'reservation' => $session->reservation->load('machine', 'customer'),
            'step' => $session->step,
            'views' => $views,
            'thumbnailUrls' => $views
                ->flatMap(fn (ReservationView $view): Collection => $view->photos)
                ->mapWithKeys(fn (Photo $photo): array => [$photo->id => $photoTemporaryUrl->for($photo, Photo::THUMB)])
                ->all(),
        ]);
    }

    /**
     * @return Collection<int, ReservationView>
     */
    private function viewsWithPhotos(PhotoSession $session): Collection
    {
        return ReservationView::query()
            ->whereBelongsTo($session->reservation)
            ->with(['photos' => fn (HasMany $photos): HasMany => $photos->where('step', $session->step)->with('media')->latest('id')])
            ->orderBy('position')
            ->get();
    }
}
