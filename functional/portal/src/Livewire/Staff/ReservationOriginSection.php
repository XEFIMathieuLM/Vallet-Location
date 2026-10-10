<?php

namespace Functional\Portal\Livewire\Staff;

use Functional\Booking\Models\Reservation;
use Functional\Portal\Models\ReservationRequest;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ReservationOriginSection extends Component
{
    public const NAME = 'portal.reservation-origin-section';

    #[Locked]
    public Reservation $reservation;

    public function render(): View
    {
        return view('portal::livewire.staff.reservation-origin-section', [
            'reservationRequest' => ReservationRequest::query()->with(['account', 'machine'])->where('reservation_id', $this->reservation->id)->first(),
        ]);
    }
}
