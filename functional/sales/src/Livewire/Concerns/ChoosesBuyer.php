<?php

namespace Functional\Sales\Livewire\Concerns;

use Functional\Booking\Data\NewCustomer;
use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Models\Customer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;

trait ChoosesBuyer
{
    public string $customerSearch = '';

    public ?int $customerId = null;

    public bool $isNewCustomer = false;

    public string $newCustomerName = '';

    public string $newCustomerPhone = '';

    public string $newCustomerEmail = '';

    public string $newCustomerType = '';

    /**
     * @return Collection<int, Customer>
     */
    #[Computed]
    public function customers(): Collection
    {
        return Customer::query()
            ->when($this->customerSearch !== '', fn ($query) => $query->whereLike('name', "%{$this->customerSearch}%"))
            ->orderBy('name')
            ->limit(20)
            ->get();
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function buyerRules(): array
    {
        if (! $this->isNewCustomer) {
            return ['customerId' => ['required', 'exists:customers,id']];
        }

        return [
            'newCustomerName' => ['required', 'string', 'max:255'],
            'newCustomerPhone' => ['nullable', 'required_without:newCustomerEmail', 'string', 'max:50'],
            'newCustomerEmail' => ['nullable', 'required_without:newCustomerPhone', 'email', 'max:255'],
            'newCustomerType' => ['required', Rule::enum(CustomerType::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function buyerAttributes(): array
    {
        return [
            'customerId' => __('sales::sales.fields.buyer'),
            'newCustomerName' => __('sales::sales.offers.customer_name'),
            'newCustomerPhone' => __('sales::sales.offers.customer_phone'),
            'newCustomerEmail' => __('sales::sales.offers.customer_email'),
            'newCustomerType' => __('booking::customers.fields.type'),
        ];
    }

    protected function selectedBuyer(): Customer|NewCustomer
    {
        if (! $this->isNewCustomer) {
            return Customer::query()->findOrFail($this->customerId);
        }

        return new NewCustomer(
            name: $this->newCustomerName,
            phone: $this->newCustomerPhone !== '' ? $this->newCustomerPhone : null,
            email: $this->newCustomerEmail !== '' ? $this->newCustomerEmail : null,
            type: CustomerType::from($this->newCustomerType),
        );
    }
}
