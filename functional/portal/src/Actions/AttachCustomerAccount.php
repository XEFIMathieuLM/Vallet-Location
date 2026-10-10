<?php

namespace Functional\Portal\Actions;

use Functional\Booking\Models\Customer;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Portal\Enums\PortalHistoryEvent;
use Functional\Portal\Exceptions\CustomerAccountAlreadyAttachedException;
use Functional\Portal\History\PortalHistory;
use Functional\Portal\Models\CustomerAccount;
use Illuminate\Contracts\Auth\Authenticatable;

final class AttachCustomerAccount
{
    public function __construct(private readonly PortalHistory $portalHistory) {}

    public function handle(CustomerAccount $account, Customer $customer, Authenticatable&AgencyMember $author): CustomerAccount
    {
        $attachedCount = CustomerAccount::query()
            ->whereKey($account->id)
            ->whereNull('customer_id')
            ->update(['customer_id' => $customer->id]);

        if ($attachedCount === 0) {
            throw CustomerAccountAlreadyAttachedException::for($account);
        }

        $this->portalHistory->record($customer, PortalHistoryEvent::AccountAttached, $author, [
            'customer_account_id' => $account->id,
            'account_email' => $account->email,
        ]);

        return $account->refresh();
    }
}
