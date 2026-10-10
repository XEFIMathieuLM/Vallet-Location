<?php

namespace Functional\Booking\Database\Factories;

use Functional\Booking\Enums\CustomerType;
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
            'type' => CustomerType::Professional,
        ];
    }

    public function reachableByEmail(): static
    {
        return $this->state(fn (): array => [
            'phone' => null,
            'email' => faker()->email(),
        ]);
    }

    public function individual(): static
    {
        return $this->state(fn (): array => ['type' => CustomerType::Individual]);
    }

    public function professional(): static
    {
        return $this->state(fn (): array => ['type' => CustomerType::Professional]);
    }

    public function untyped(): static
    {
        return $this->state(fn (): array => ['type' => null]);
    }
}
