<?php

namespace Functional\Booking\Database\Factories;

use Functional\Booking\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => faker()->company(),
            'phone' => faker()->customerPhoneNumber(),
            'email' => null,
        ];
    }
}
