<?php

namespace Functional\Accounts\Livewire;

use Flux\Flux;
use Functional\Accounts\Access\AccountsPermission;
use Functional\Accounts\Actions\DesignateKeyAccount;
use Functional\Accounts\Actions\RevokeKeyAccount;
use Functional\Accounts\Livewire\Concerns\ActsAsAuthor;
use Functional\Accounts\Models\KeyAccount;
use Functional\Billing\Models\CustomerBillingAccount;
use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Models\Customer;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read Collection<int, KeyAccount> $keyAccounts
 * @property-read Collection<int, Customer> $candidates
 * @property-read array<int, string> $billingRefs
 */
class KeyAccounts extends Component
{
    use ActsAsAuthor, DisplaysRefusals;

    private const CANDIDATE_LIMIT = 20;

    public string $search = '';

    /**
     * @return Collection<int, KeyAccount>
     */
    #[Computed]
    public function keyAccounts(): Collection
    {
        return KeyAccount::query()
            ->with(['customer', 'designator'])
            ->join('customers', 'customers.id', '=', 'key_accounts.customer_id')
            ->orderBy('customers.name')
            ->select('key_accounts.*')
            ->get();
    }

    /**
     * @return Collection<int, Customer>
     */
    #[Computed]
    public function candidates(): Collection
    {
        if (trim($this->search) === '') {
            return new Collection;
        }

        return Customer::query()
            ->where('type', CustomerType::Professional)
            ->whereLike('name', '%'.trim($this->search).'%')
            ->whereNotIn('id', KeyAccount::query()->select('customer_id'))
            ->orderBy('name')
            ->limit(self::CANDIDATE_LIMIT)
            ->get();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function billingRefs(): array
    {
        $customerIds = [...$this->keyAccounts->pluck('customer_id')->all(), ...$this->candidates->modelKeys()];

        return CustomerBillingAccount::query()->whereIn('customer_id', $customerIds)->pluck('external_ref', 'customer_id')->all();
    }

    public function designate(int $customerId, DesignateKeyAccount $designateKeyAccount): void
    {
        Gate::authorize(AccountsPermission::ManageKeyAccounts->value);

        $customer = Customer::query()->findOrFail($customerId);
        $designateKeyAccount->handle($this->author(), $customer);
        Flux::toast(text: __('accounts::key_accounts.screen.designated_toast', ['customer' => $customer->name]), variant: 'success');
    }

    public function revoke(int $customerId, RevokeKeyAccount $revokeKeyAccount): void
    {
        Gate::authorize(AccountsPermission::ManageKeyAccounts->value);

        $customer = Customer::query()->findOrFail($customerId);
        $revokeKeyAccount->handle($this->author(), $customer);
        Flux::modal("revoke-key-account-{$customerId}")->close();
        Flux::toast(text: __('accounts::key_accounts.screen.revoked', ['customer' => $customer->name]), variant: 'success');
    }

    public function render(): View
    {
        return view('accounts::livewire.key-accounts')->title(__('accounts::key_accounts.screen.title'));
    }
}
