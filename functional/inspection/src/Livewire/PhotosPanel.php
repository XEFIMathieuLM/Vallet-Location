<?php

namespace Functional\Inspection\Livewire;

use Functional\Booking\Models\Reservation;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Inspection\Actions\DeletePhoto;
use Functional\Inspection\Actions\MissingViews;
use Functional\Inspection\Actions\OpenPhotoSession;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Exceptions\StepNotOpenException;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\PhotoSession;
use Functional\Inspection\Models\ReservationView;
use Functional\Inspection\Support\QrCodeSvg;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PhotosPanel extends Component
{
    use DisplaysRefusals;

    public const READINESS_EVENT = 'reservation-transition-readiness';

    #[Locked]
    public Reservation $reservation;

    #[Locked]
    public ?string $token = null;

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
        Gate::authorize('reservations.manage');

        $step = $this->openStep() ?? throw StepNotOpenException::forAnyStep();

        $this->token = app(OpenPhotoSession::class)->handle($this->reservation, $step, Auth::user() ?? abort(401));
    }

    public function deletePhoto(int $photoId): void
    {
        Gate::authorize('reservations.manage');

        $photo = Photo::query()->where('reservation_id', $this->reservation->id)->findOrFail($photoId);

        app(DeletePhoto::class)->handle($photo, Auth::user());

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
            ->where('reservation_id', $this->reservation->id)
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
            ->where('reservation_id', $this->reservation->id)
            ->with(['photos' => fn (HasMany $photos): HasMany => $photos->with(['media', 'session.author'])->oldest('id')])
            ->orderBy('position')
            ->get();
    }

    private function announceReadiness(): void
    {
        $hasFrozenViews = ReservationView::query()->where('reservation_id', $this->reservation->id)->exists();

        foreach (InspectionStep::cases() as $step) {
            $isReady = $hasFrozenViews && app(MissingViews::class)->for($this->reservation, $step)->isEmpty();

            $this->dispatch(self::READINESS_EVENT, step: $step->value, is_ready: $isReady);
        }
    }
}
