<?php

namespace Functional\Accounts\Database\Factories;

use Carbon\CarbonImmutable;
use Functional\Accounts\Models\KeyAccount;
use Functional\Booking\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<KeyAccount>
 */
class KeyAccountFactory extends Factory
{
    protected $model = KeyAccount::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory()->professional(),
            'designated_by' => fn () => Factory::factoryForModel($this->userModel()),
            'designated_at' => CarbonImmutable::now(),
        ];
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        return config('auth.providers.users.model');
    }
}
