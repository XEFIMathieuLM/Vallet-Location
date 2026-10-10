<?php

namespace Functional\Billing\Livewire;

use Flux\Flux;
use Functional\Billing\Actions\RetryTransmission;
use Functional\Billing\Actions\SetCustomerBillingRef;
use Functional\Billing\Enums\BillingPermission;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Queries\TransmissionsToHandle;
use Functional\Booking\Models\Customer;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Transmissions extends Component
{
    use DisplaysRefusals;

    /**
     * @var array<int|string, string>
     */
    public array $customerRefs = [];

    public function saveCustomerRef(int $customerId, SetCustomerBillingRef $setCustomerBillingRef): void
    {
        Gate::authorize(BillingPermission::Manage->value);

        $this->validate(["customerRefs.{$customerId}" => ['required', 'string', 'max:100']], attributes: ["customerRefs.{$customerId}" => __('billing::transmissions.screen.customer_ref')]);

        $setCustomerBillingRef->handle(Customer::query()->findOrFail($customerId), $this->customerRefs[$customerId]);
        Flux::toast(text: __('billing::transmissions.screen.customer_ref_saved'), variant: 'success');
    }

    public function retry(int $transmissionId, RetryTransmission $retryTransmission): void
    {
        Gate::authorize(BillingPermission::Manage->value);

        $retryTransmission->handle(Transmission::query()->findOrFail($transmissionId));
        Flux::toast(text: __('billing::transmissions.screen.retried'), variant: 'success');
    }

    public function render(TransmissionsToHandle $transmissionsToHandle): View
    {
        return view('billing::livewire.transmissions', [
            'transmissions' => $transmissionsToHandle->query()
                ->with(['reservation.machine', 'reservation.customer', 'billablePeriod', 'damageSettlement'])
                ->orderBy('created_at')
                ->get(),
        ])->title(__('billing::transmissions.screen.title'));
    }
}
