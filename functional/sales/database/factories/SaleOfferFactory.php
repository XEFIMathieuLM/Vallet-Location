<?php

namespace Functional\Sales\Database\Factories;

use Carbon\CarbonImmutable;
use Functional\Billing\Money\Money;
use Functional\Booking\Models\Customer;
use Functional\Sales\Enums\OfferStatus;
use Functional\Sales\Models\Sale;
use Functional\Sales\Models\SaleOffer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<SaleOffer>
 */
class SaleOfferFactory extends Factory
{
    protected $model = SaleOffer::class;

    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'customer_id' => Customer::factory(),
            'amount' => Money::fromStored(faker()->number(4000, 70000) * 100),
            'offered_on' => CarbonImmutable::today(),
            'status' => OfferStatus::Pending,
            'recorded_by' => fn () => Factory::factoryForModel($this->userModel()),
        ];
    }

    public function accepted(): static
    {
        return $this->decided(OfferStatus::Accepted);
    }

    public function decided(OfferStatus $status): static
    {
        return $this->state(fn (): array => [
            'status' => $status,
            'decided_by' => fn (array $expandedAttributes): mixed => $expandedAttributes['recorded_by'],
            'decided_at' => CarbonImmutable::now(),
        ]);
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        return config('auth.providers.users.model');
    }
}
