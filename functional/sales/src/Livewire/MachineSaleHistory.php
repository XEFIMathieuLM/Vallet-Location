<?php

namespace Functional\Sales\Livewire;

use Functional\Fleet\Models\Machine;
use Functional\Sales\Models\Sale;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

class MachineSaleHistory extends Component
{
    #[Locked]
    public Machine $machine;

    public function render(): View
    {
        $sales = Sale::query()->with(['offers.customer', 'buyer', 'agency'])->whereBelongsTo($this->machine)->latest('id')->get();
        $activities = Activity::query()
            ->where('subject_type', (new Sale)->getMorphClass())
            ->whereIn('subject_id', $sales->modelKeys())
            ->with('causer')
            ->latest('id')
            ->get()
            ->groupBy('subject_id');

        return view('sales::livewire.machine-sale-history', ['sales' => $sales, 'activities' => $activities])
            ->title(__('sales::sales.machine_history.title', ['reference' => $this->machine->reference]));
    }
}
