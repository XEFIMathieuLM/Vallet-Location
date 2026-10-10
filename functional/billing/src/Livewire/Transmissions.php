<?php

namespace Functional\Billing\Livewire;

use Functional\Billing\Actions\RetryTransmission;
use Functional\Billing\Actions\SetCustomerBillingRef;
use Functional\Billing\Livewire\Concerns\DisplaysBillingRefusals;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Queries\TransmissionsToHandle;
use Functional\Booking\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Transmissions extends Component
{
    use DisplaysBillingRefusals;

    /**
     * @var array<int|string, string>
     */
    public array $customerRefs = [];

    public function saveCustomerRef(int $customerId, SetCustomerBillingRef $setCustomerBillingRef): void
    {
        Gate::authorize('billing.manage');

        $this->validate(["customerRefs.{$customerId}" => ['required', 'string', 'max:100']], attributes: ["customerRefs.{$customerId}" => __('billing::transmissions.screen.customer_ref')]);

        $setCustomerBillingRef->handle(Customer::query()->findOrFail($customerId), $this->customerRefs[$customerId]);
    }

    public function retry(int $transmissionId, RetryTransmission $retryTransmission): void
    {
        Gate::authorize('billing.manage');

        $retryTransmission->handle(Transmission::query()->findOrFail($transmissionId));
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
