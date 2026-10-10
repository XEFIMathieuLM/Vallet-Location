<?php

namespace Functional\Portal\Livewire\Customer;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('portal::layouts.portal')]
class MyRequests extends Component
{
    public function render(): View
    {
        return view('portal::livewire.customer.my-requests')->title(__('portal::navigation.requests'));
    }
}
