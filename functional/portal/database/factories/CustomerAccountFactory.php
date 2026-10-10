<?php

namespace Functional\Portal\Database\Factories;

use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Models\Customer;
use Functional\Portal\Models\CustomerAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<CustomerAccount>
 */
class CustomerAccountFactory extends Factory
{
    protected $model = CustomerAccount::class;

    private static ?string $hashedPassword = null;

    public function definition(): array
    {
        return [
            'name' => faker()->company(),
            'email' => mb_strtolower(faker()->email()),
            'email_verified_at' => now(),
            'phone' => faker()->customerPhoneNumber(),
            'declared_type' => CustomerType::Professional,
            'password' => self::$hashedPassword ??= Hash::make('password'),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (): array => ['email_verified_at' => null]);
    }

    public function individual(): static
    {
        return $this->state(fn (): array => ['declared_type' => CustomerType::Individual]);
    }

    public function attachedTo(Customer $customer): static
    {
        return $this->state(fn (): array => ['customer_id' => $customer->id]);
    }
}
