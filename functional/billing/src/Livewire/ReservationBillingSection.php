<?php

namespace Functional\Billing\Livewire;

use Functional\Billing\Models\DamageSettlement;
use Functional\Billing\Models\Transmission;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Models\Damage;
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
            'damageSettlements' => DamageSettlement::query()
                ->whereIn('damage_id', Damage::query()->select('id')->where('reservation_id', $this->reservation->id))
                ->with(['damage.view', 'settler', 'transmission'])
                ->orderBy('settled_at')
                ->get(),
        ]);
    }
}
