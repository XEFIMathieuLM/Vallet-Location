<?php

namespace Functional\Billing\Livewire;

use Functional\Billing\Queries\TransmissionsToHandle;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class BillingAlert extends Component
{
    public function render(TransmissionsToHandle $transmissionsToHandle): View
    {
        return view('billing::livewire.billing-alert', [
            'transmissionsCount' => $transmissionsToHandle->query()->count(),
        ]);
    }
}
