<?php

namespace Functional\Billing\Livewire;

use Functional\Billing\Models\BillablePeriod;
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
                ->whereBelongsTo($this->reservation)
                ->has('billablePeriod')
                ->with('billablePeriod')
                ->orderBy(BillablePeriod::query()->select('start_date')->whereColumn('billable_periods.id', 'transmissions.billable_period_id'))
                ->get(),
            'damageSettlements' => DamageSettlement::query()
                ->whereIn('damage_id', Damage::query()->select('id')->whereBelongsTo($this->reservation))
                ->with(['damage.view', 'settler', 'transmission'])
                ->orderBy('settled_at')
                ->get(),
        ]);
    }
}
