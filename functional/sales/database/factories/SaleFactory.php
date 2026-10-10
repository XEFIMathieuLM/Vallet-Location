<?php

namespace Functional\Sales\Database\Factories;

use Carbon\CarbonImmutable;
use Functional\Billing\Money\Money;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Sales\Enums\SaleStatus;
use Functional\Sales\Models\Sale;
use Functional\Sales\Models\SaleOffer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        return [
            'machine_id' => Machine::factory(),
            'status' => SaleStatus::Listed,
            'asking_price' => Money::fromStored(faker()->number(5000, 80000) * 100),
            'year_of_manufacture' => faker()->number(2008, 2022),
            'operating_hours' => faker()->number(500, 9000),
            'condition' => faker()->sentences(1),
            'comment' => null,
            'agency_id' => Agency::factory(),
            'listed_by' => fn () => Factory::factoryForModel($this->userModel()),
        ];
    }

    public function listed(): static
    {
        return $this->state(fn (): array => ['status' => SaleStatus::Listed]);
    }

    public function reserved(?CarbonImmutable $plannedHandoverDate = null, ?Money $finalPrice = null): static
    {
        return $this->listed()->afterCreating(function (Sale $sale) use ($plannedHandoverDate, $finalPrice): void {
            $acceptedOffer = SaleOffer::factory()->accepted()->create([
                'sale_id' => $sale->id,
                'recorded_by' => $sale->listed_by,
                'amount' => $finalPrice ?? $sale->asking_price,
            ]);

            $sale->update([
                'status' => SaleStatus::Reserved,
                'buyer_id' => $acceptedOffer->customer_id,
                'accepted_offer_id' => $acceptedOffer->id,
                'final_price' => $acceptedOffer->amount,
                'planned_handover_date' => $plannedHandoverDate ?? CarbonImmutable::today()->addDays(10),
            ]);
        });
    }

    public function sold(): static
    {
        return $this->reserved()->afterCreating(function (Sale $sale): void {
            $sale->update([
                'status' => SaleStatus::Sold,
                'handed_over_on' => CarbonImmutable::today(),
                'handed_over_by' => $sale->listed_by,
            ]);
        });
    }

    public function cancelled(string $cancellationReason = 'Machine conservée'): static
    {
        return $this->state(fn (): array => [
            'status' => SaleStatus::Cancelled,
            'cancellation_reason' => $cancellationReason,
            'cancelled_by' => fn () => Factory::factoryForModel($this->userModel()),
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
