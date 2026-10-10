<?php

namespace Functional\Accounts\Actions;

use Carbon\CarbonImmutable;
use Functional\Accounts\Enums\AccountsHistoryEvent;
use Functional\Accounts\Exceptions\KeyAccountRefusedException;
use Functional\Accounts\Models\KeyAccount;
use Functional\Accounts\Support\AccountsHistory;
use Functional\Billing\Models\CustomerBillingAccount;
use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Events\CustomerChanged;
use Functional\Booking\Models\Customer;
use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

final class DesignateKeyAccount
{
    public function __construct(private readonly AccountsHistory $accountsHistory) {}

    public function handle(Authenticatable&AgencyMember $author, Customer $customer): KeyAccount
    {
        $keyAccount = DB::transaction(function () use ($author, $customer): KeyAccount {
            $lockedCustomer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $this->ensureCanBeDesignated($lockedCustomer);

            $keyAccount = KeyAccount::query()->create([
                'customer_id' => $lockedCustomer->id,
                'designated_by' => $author->getAuthIdentifier(),
                'designated_at' => CarbonImmutable::now(),
            ]);
            $this->accountsHistory->record($lockedCustomer, AccountsHistoryEvent::KeyAccountDesignated, $author);

            return $keyAccount;
        });

        CustomerChanged::dispatch($customer, ['key_account']);

        return $keyAccount;
    }

    private function ensureCanBeDesignated(Customer $customer): void
    {
        if ($customer->type !== CustomerType::Professional) {
            throw KeyAccountRefusedException::notProfessional($customer);
        }

        $hasBillingRef = CustomerBillingAccount::query()
            ->where('customer_id', $customer->id)
            ->where('external_ref', '<>', '')
            ->exists();

        if (! $hasBillingRef) {
            throw KeyAccountRefusedException::missingBillingRef($customer);
        }

        if (KeyAccount::query()->where('customer_id', $customer->id)->exists()) {
            throw KeyAccountRefusedException::alreadyDesignated($customer);
        }
    }
}
