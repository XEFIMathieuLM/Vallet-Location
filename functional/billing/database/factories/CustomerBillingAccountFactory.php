<?php

namespace Functional\Billing\Database\Factories;

use Functional\Billing\Models\CustomerBillingAccount;
use Functional\Booking\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerBillingAccount>
 */
class CustomerBillingAccountFactory extends Factory
{
    protected $model = CustomerBillingAccount::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'external_ref' => 'CLI-'.faker()->number(10000, 99999),
        ];
    }
}
