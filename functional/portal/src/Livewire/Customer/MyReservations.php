<?php

namespace Functional\Portal\Livewire\Customer;

use Functional\Portal\Livewire\Concerns\ActsAsCustomerAccount;
use Functional\Portal\Queries\AccountReservations;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('portal::layouts.portal')]
class MyReservations extends Component
{
    use ActsAsCustomerAccount, WithPagination;

    public function render(AccountReservations $accountReservations): View
    {
        return view('portal::livewire.customer.my-reservations', [
            'reservations' => $accountReservations->paginate($this->customerAccount()),
        ])->title(__('portal::navigation.reservations'));
    }
}
