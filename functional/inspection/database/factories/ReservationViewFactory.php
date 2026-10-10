<?php

namespace Functional\Inspection\Database\Factories;

use Functional\Booking\Models\Reservation;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservationView>
 */
class ReservationViewFactory extends Factory
{
    protected $model = ReservationView::class;

    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'label' => faker()->inspectionViewName(),
            'position' => fn (array $attributes): int => (int) ReservationView::query()->where('reservation_id', $attributes['reservation_id'])->max('position') + 1,
        ];
    }
}
