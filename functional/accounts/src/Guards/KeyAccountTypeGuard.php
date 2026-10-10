<?php

namespace Functional\Accounts\Guards;

use Functional\Accounts\Exceptions\KeyAccountMustStayProfessionalException;
use Functional\Accounts\Support\KeyAccounts;
use Functional\Booking\Contracts\CustomerChangeGuard;
use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Models\Customer;

final class KeyAccountTypeGuard implements CustomerChangeGuard
{
    public function __construct(private readonly KeyAccounts $keyAccounts) {}

    public function beforeTypeChange(Customer $customer, CustomerType $newType): void
    {
        if ($newType !== CustomerType::Professional && $this->keyAccounts->isKeyAccount($customer->id)) {
            throw KeyAccountMustStayProfessionalException::forCustomer($customer);
        }
    }
}
