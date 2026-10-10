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
            'label' => ucfirst(faker()->words(1)),
            'position' => faker()->unique()->number(1, 99999),
        ];
    }
}
