<?php

namespace Functional\Booking\Tests\Doubles;

use Functional\Booking\Contracts\CustomerChangeGuard;
use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Models\Customer;

final class RefusingCustomerChangeGuard implements CustomerChangeGuard
{
    public function beforeTypeChange(Customer $customer, CustomerType $newType): void
    {
        throw GuardRefusalException::missingPhotos('Requalification refusée.');
    }
}
