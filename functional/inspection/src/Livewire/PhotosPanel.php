<?php

namespace Functional\Inspection\Livewire;

use Flux\Flux;
use Functional\Booking\Access\BookingPermission;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Inspection\Actions\DeletePhoto;
use Functional\Inspection\Actions\OpenPhotoSession;
use Functional\Inspection\Completeness\ViewCompleteness;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Exceptions\StepNotOpenException;
use Functional\Inspection\Livewire\Concerns\ActsAsAgencyMember;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\PhotoSession;
use Functional\Inspection\Models\ReservationView;
use Functional\Inspection\QrCodes\QrCodeSvg;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PhotosPanel extends Component
{
    use ActsAsAgencyMember, DisplaysRefusals;

    public const READINESS_EVENT = 'reservation-transition-readiness';

    public const SECTION = 'inspection.photos-panel';

    private const DELETE_PHOTO_MODAL = 'delete-photo';

    #[Locked]
    public Reservation $reservation;

    #[Locked]
    public ?string $token = null;

    public ?int $photoIdToDelete = null;

    public function mount(Reservation $reservation): void
    {
        $this->reservation = $reservation;
        $this->announceReadiness();
    }

    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        $channel = "echo-private:reservation.{$this->reservation->id}";

        return [
            "{$channel},.photo.changed" => 'refreshPhotos',
            "{$channel},.photo-session.changed" => 'refreshSession',
        ];
    }

    public function generate(): void
    {
        Gate::authorize(BookingPermission::ManageReservations->value);

        $step = $this->openStep() ?? throw StepNotOpenException::forAnyStep($this->reservation);

        $this->token = app(OpenPhotoSession::class)->handle($this->reservation, $step, $this->agencyMember());
    }

    public function confirmPhotoDeletion(int $photoId): void
    {
        $this->photoIdToDelete = $photoId;

        Flux::modal(self::DELETE_PHOTO_MODAL)->show();
    }

    public function deletePhoto(int $photoId): void
    {
        Gate::authorize(BookingPermission::ManageReservations->value);

        $photo = Photo::query()->whereBelongsTo($this->reservation)->findOrFail($photoId);

        app(DeletePhoto::class)->handle($photo, $this->agencyMember());

        Flux::modal(self::DELETE_PHOTO_MODAL)->close();
        $this->announceReadiness();
    }

    public function refreshPhotos(): void
    {
        $this->announceReadiness();
    }

    public function refreshSession(): void
    {
        if ($this->token !== null && $this->activeSession()?->token_hash !== PhotoSession::hashToken($this->token)) {
            $this->token = null;
        }
    }

    public function render(): View
    {
        $this->reservation->refresh();
        $activeSession = $this->activeSession();

        if ($activeSession === null || $this->token === null || $activeSession->token_hash !== PhotoSession::hashToken($this->token)) {
            $this->token = null;
        }

        return view('inspection::livewire.photos-panel', [
            'openStep' => $this->openStep(),
            'activeSession' => $activeSession,
            'qrCode' => $this->token !== null ? app(QrCodeSvg::class)->for(route('inspection.phone', $this->token)) : null,
            'views' => $this->views(),
            'canCompare' => app(ViewCompleteness::class)->for($this->reservation)->hasPhotosFor(InspectionStep::Return),
        ]);
    }

    private function openStep(): ?InspectionStep
    {
        return collect(InspectionStep::cases())->first(fn (InspectionStep $step): bool => $step->isOpenFor($this->reservation));
    }

    private function activeSession(): ?PhotoSession
    {
        $openStep = $this->openStep();

        if ($openStep === null) {
            return null;
        }

        return PhotoSession::query()
            ->whereBelongsTo($this->reservation)
            ->where('step', $openStep)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
    }

    /**
     * @return Collection<int, ReservationView>
     */
    private function views(): Collection
    {
        return ReservationView::query()
            ->whereBelongsTo($this->reservation)
            ->with(['photos' => fn (HasMany $photos): HasMany => $photos->with(['media', 'session.author'])->oldest('id')])
            ->orderBy('position')
            ->get();
    }

    private function announceReadiness(): void
    {
        $completeness = app(ViewCompleteness::class)->for($this->reservation);

        foreach (InspectionStep::cases() as $step) {
            $isReady = $completeness->isCompleteFor($step);

            $this->dispatch(self::READINESS_EVENT, step: $step->transition()->value, section: self::SECTION, is_ready: $isReady);
        }
    }
}
