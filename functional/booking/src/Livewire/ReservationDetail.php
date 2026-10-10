<?php

namespace Functional\Booking\Livewire;

use Functional\Booking\Actions\CancelReservation;
use Functional\Booking\Actions\DepartReservation;
use Functional\Booking\Actions\ReturnReservation;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Enums\ReturnCondition;
use Functional\Booking\Extensions\ReservationDetailSections;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ReservationDetail extends Component
{
    use DisplaysRefusals;

    public const DEPARTURE_STEP = 'departure';

    public const RETURN_STEP = 'return';

    public Reservation $reservation;

    /**
     * @var array<string, bool>
     */
    public array $readinessBySteps = [];

    public function mount(Reservation $reservation): void
    {
        $this->reservation = $reservation->load(['machine.category', 'machine.agency', 'customer', 'agency', 'author']);
    }

    public function depart(DepartReservation $departReservation): void
    {
        $departReservation->handle($this->reservation);
    }

    public function returnMachine(string $returnCondition, ReturnReservation $returnReservation): void
    {
        $returnReservation->handle($this->reservation, ReturnCondition::from($returnCondition));
    }

    public function cancel(CancelReservation $cancelReservation): void
    {
        $cancelReservation->handle($this->reservation);
    }

    #[On('reservation-transition-readiness')]
    public function updateTransitionReadiness(string $step, bool $is_ready): void
    {
        $this->readinessBySteps[$step] = $is_ready;
    }

    public function isReadyFor(string $step): bool
    {
        if (app(ReservationDetailSections::class)->isEmpty()) {
            return true;
        }

        return $this->readinessBySteps[$step] ?? false;
    }

    public function render(): View
    {
        $this->reservation->refresh();

        return view('booking::livewire.reservation-detail', [
            'sections' => app(ReservationDetailSections::class)->all(),
            'isConfirmed' => $this->reservation->status === ReservationStatus::Confirmed,
            'isInProgress' => $this->reservation->status === ReservationStatus::InProgress,
            'returnConditions' => ReturnCondition::cases(),
        ])->title(__('booking::reservations.detail.title', ['reference' => $this->reservation->machine->reference]));
    }
}
