<?php

namespace Functional\Billing\Livewire;

use Functional\Billing\Models\Transmission;
use Functional\Booking\Models\Reservation;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ReservationBillingSection extends Component
{
    #[Locked]
    public Reservation $reservation;

    public function render(): View
    {
        return view('billing::livewire.reservation-billing-section', [
            'periodTransmissions' => Transmission::query()
                ->where('reservation_id', $this->reservation->id)
                ->whereNotNull('billable_period_id')
                ->with('billablePeriod')
                ->get()
                ->sortBy(fn (Transmission $transmission): string => $transmission->billablePeriod?->start_date->toDateString() ?? ''),
        ]);
    }
}
