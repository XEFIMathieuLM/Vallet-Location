<?php

namespace Functional\Inspection\Livewire;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Inspection\Actions\DeletePhoto;
use Functional\Inspection\Actions\FindActivePhotoSession;
use Functional\Inspection\Actions\StorePhoto;
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
use Throwable;

#[Layout('inspection::layouts.phone')]
class PhoneCapture extends Component
{
    use WithFileUploads;

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

    public function exception(Throwable $e, callable $stopPropagation): void
    {
        if ($e instanceof RefusalException) {
            $this->addError('refusal', $e->getMessage());
            $stopPropagation();
        }
    }

    public function render(): View
    {
        $session = app(FindActivePhotoSession::class)->find($this->token);

        if ($session === null) {
            return view('inspection::phone.expired');
        }

        return view('inspection::livewire.phone-capture', [
            'reservation' => $session->reservation->load('machine', 'customer'),
            'step' => $session->step,
            'views' => $this->viewsWithPhotos($session),
        ]);
    }

    /**
     * @return Collection<int, ReservationView>
     */
    private function viewsWithPhotos(PhotoSession $session): Collection
    {
        return ReservationView::query()
            ->where('reservation_id', $session->reservation_id)
            ->with(['photos' => fn (HasMany $photos): HasMany => $photos->where('step', $session->step)->with('media')->latest('id')])
            ->orderBy('position')
            ->get();
    }
}
