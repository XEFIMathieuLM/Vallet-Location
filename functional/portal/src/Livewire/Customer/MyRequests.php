<?php

namespace Functional\Portal\Livewire\Customer;

use Flux\Flux;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Portal\Actions\CancelReservationRequest;
use Functional\Portal\Livewire\Concerns\ActsAsCustomerAccount;
use Functional\Portal\Models\ReservationRequest;
use Functional\Portal\Pricing\IndicativePriceFormatter;
use Functional\Portal\Queries\AccountRequests;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('portal::layouts.portal')]
class MyRequests extends Component
{
    use ActsAsCustomerAccount, DisplaysRefusals, WithPagination;

    public function cancel(int $reservationRequestId, CancelReservationRequest $cancelReservationRequest): void
    {
        $cancelReservationRequest->handle(ReservationRequest::query()->findOrFail($reservationRequestId), $this->customerAccount());
        Flux::modals()->close();
        Flux::toast(text: __('portal::requests.cancelled'), variant: 'success');
    }

    public function render(AccountRequests $accountRequests, IndicativePriceFormatter $priceFormatter): View
    {
        return view('portal::livewire.customer.my-requests', [
            'reservationRequests' => $accountRequests->paginate($this->customerAccount()),
            'priceFormatter' => $priceFormatter,
        ])->title(__('portal::navigation.requests'));
    }
}
