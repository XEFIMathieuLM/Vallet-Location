<?php

namespace Functional\Deposit\Livewire;

use Functional\Booking\Enums\ReservationTransition;
use Functional\Booking\Models\Reservation;
use Functional\Deposit\Queries\DepositSituation;
use Functional\Deposit\ValueObjects\DepositSituationResult;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class ReservationDepositSection extends Component
{
    public const NAME = 'deposit.reservation-section';

    private const READINESS_EVENT = 'reservation-transition-readiness';

    #[Locked]
    public Reservation $reservation;

    public function mount(Reservation $reservation): void
    {
        $this->reservation = $reservation;
        $this->reportReadiness();
    }

    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return [
            'echo-private:fleet,.deposit.changed' => 'refreshFromBroadcast',
            'echo-private:fleet,.reservation.changed' => 'refreshFromBroadcast',
        ];
    }

    /**
     * @param  array{reservation_id?: int, id?: int}  $payload
     */
    public function refreshFromBroadcast(array $payload): void
    {
        if (($payload['reservation_id'] ?? $payload['id'] ?? null) === $this->reservation->id) {
            $this->refreshSituation();
        }
    }

    #[On('deposit-updated')]
    public function refreshSituation(): void
    {
        $this->reservation->refresh();
        $this->reportReadiness();
    }

    public function render(): View
    {
        return view('deposit::livewire.reservation-deposit-section', [
            'situation' => $this->situation(),
        ]);
    }

    private function situation(): DepositSituationResult
    {
        return app(DepositSituation::class)->for($this->reservation);
    }

    private function reportReadiness(): void
    {
        $this->dispatch(self::READINESS_EVENT, step: ReservationTransition::Departure->value, section: self::NAME, is_ready: $this->situation()->isDepartureReady());
    }
}
