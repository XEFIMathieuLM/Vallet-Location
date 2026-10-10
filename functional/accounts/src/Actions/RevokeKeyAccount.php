<?php

namespace Functional\Accounts\Actions;

use Functional\Accounts\Enums\AccountsHistoryEvent;
use Functional\Accounts\Models\KeyAccount;
use Functional\Accounts\Support\AccountsHistory;
use Functional\Booking\Events\CustomerChanged;
use Functional\Booking\Models\Customer;
use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

final class RevokeKeyAccount
{
    public function __construct(private readonly AccountsHistory $accountsHistory) {}

    public function handle(Authenticatable&AgencyMember $author, Customer $customer): void
    {
        $isRevoked = DB::transaction(function () use ($author, $customer): bool {
            $lockedCustomer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $deletedRows = KeyAccount::query()->where('customer_id', $lockedCustomer->id)->delete();

            if ($deletedRows === 0) {
                return false;
            }

            $this->accountsHistory->record($lockedCustomer, AccountsHistoryEvent::KeyAccountRevoked, $author);

            return true;
        });

        if ($isRevoked) {
            CustomerChanged::dispatch($customer, ['key_account']);
        }
    }
}
