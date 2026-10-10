<?php

namespace Functional\Booking\Contracts;

use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Models\Customer;
use Functional\Fleet\Exceptions\RefusalException;

interface CustomerChangeGuard
{
    /**
     * @throws RefusalException
     */
    public function beforeTypeChange(Customer $customer, CustomerType $newType): void;
}
