<?php

namespace Functional\Billing\Actions;

use Functional\Billing\Models\CustomerBillingAccount;
use Functional\Booking\Models\Customer;

final class SetCustomerBillingRef
{
    public function handle(Customer $customer, string $externalRef): CustomerBillingAccount
    {
        return CustomerBillingAccount::query()->updateOrCreate(['customer_id' => $customer->id], ['external_ref' => trim($externalRef)]);
    }
}
