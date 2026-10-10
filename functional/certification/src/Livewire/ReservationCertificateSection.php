<?php

namespace Functional\Certification\Livewire;

use Functional\Booking\Enums\ReservationTransition;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Calendar\CertificationCalendar;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ReservationCertificateSection extends Component
{
    use DisplaysRefusals;

    public const NAME = 'certification.reservation-section';

    public const READINESS_EVENT = 'reservation-transition-readiness';

    #[Locked]
    public Reservation $reservation;

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
        return ['echo-private:fleet,.certificate.changed' => 'refreshCertificate'];
    }

    /**
     * @param  array{reservation_id?: int}  $payload
     */
    public function refreshCertificate(array $payload = []): void
    {
        if (($payload['reservation_id'] ?? null) === $this->reservation->id) {
            $this->announceReadiness();
        }
    }

    public function render(): View
    {
        return view('certification::livewire.reservation-certificate-section', [
            'isConcerned' => $this->isConcerned(),
            'certificate' => $this->certificate(),
        ]);
    }

    protected function announceReadiness(): void
    {
        $isReady = ! $this->isConcerned() || ($this->certificate()?->status->isDelivered() ?? false);

        $this->dispatch(self::READINESS_EVENT, step: ReservationTransition::Departure->value, section: self::NAME, is_ready: $isReady);
    }

    protected function certificate(): ?ReservationCertificate
    {
        return ReservationCertificate::query()->whereBelongsTo($this->reservation)->with('lastDispatch')->first();
    }

    private function isConcerned(): bool
    {
        return app(CertificationCalendar::class)->isLive() && $this->reservation->machine->is_subject_to_vgp;
    }
}
