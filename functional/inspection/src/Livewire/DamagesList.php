<?php

namespace Functional\Inspection\Livewire;

use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Inspection\Livewire\Concerns\ResolvesDamages;
use Functional\Inspection\Queries\ReservationsToReinvoice;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class DamagesList extends Component
{
    use DisplaysRefusals, ResolvesDamages;

    #[On('echo-private:fleet,.damage.changed')]
    public function refreshDamages(): void {}

    public function render(): View
    {
        return view('inspection::livewire.damages-list', [
            'reservationsDamages' => app(ReservationsToReinvoice::class)->get(),
        ])->title(__('inspection::damages.list.title'));
    }
}
