<?php

namespace Functional\Booking\Tests\Doubles;

use Functional\Booking\Contracts\CustomerChangeGuard;
use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Models\Customer;

final class RecordingCustomerChangeGuard implements CustomerChangeGuard
{
    /**
     * @var list<array{previous: CustomerType|null, next: CustomerType}>
     */
    public static array $calls = [];

    public function beforeTypeChange(Customer $customer, CustomerType $newType): void
    {
        self::$calls[] = ['previous' => $customer->type, 'next' => $newType];
    }
}
