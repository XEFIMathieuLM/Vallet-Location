<?php

namespace Functional\Portal\Livewire\Customer;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('portal::layouts.portal')]
class Search extends Component
{
    public function render(): View
    {
        return view('portal::livewire.customer.search')->title(__('portal::search.title'));
    }
}
