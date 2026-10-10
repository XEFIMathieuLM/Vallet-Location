<?php

namespace Functional\Accounts\Database\Factories;

use Carbon\CarbonImmutable;
use Functional\Accounts\Models\ReservationPurchaseOrder;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<ReservationPurchaseOrder>
 */
class ReservationPurchaseOrderFactory extends Factory
{
    protected $model = ReservationPurchaseOrder::class;

    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'number' => faker()->purchaseOrderNumber(),
            'entered_by' => fn () => Factory::factoryForModel($this->userModel()),
            'agency_id' => Agency::factory(),
            'entered_at' => CarbonImmutable::now(),
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
