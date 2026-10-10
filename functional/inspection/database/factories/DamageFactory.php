<?php

namespace Functional\Inspection\Database\Factories;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Inspection\Models\Damage;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Damage>
 */
class DamageFactory extends Factory
{
    protected $model = Damage::class;

    public function definition(): array
    {
        return [
            'reservation_view_id' => ReservationView::factory(),
            'reservation_id' => fn (array $attributes): mixed => ReservationView::query()->whereKey($attributes['reservation_view_id'])->value('reservation_id'),
            'comment' => faker()->sentences(1),
            'reported_by' => User::factory(),
            'reported_at' => CarbonImmutable::now(),
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => [
            'resolved_by' => User::factory(),
            'resolved_at' => CarbonImmutable::now(),
        ]);
    }
}
