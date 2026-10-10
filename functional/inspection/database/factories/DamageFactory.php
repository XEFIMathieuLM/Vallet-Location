<?php

namespace Functional\Inspection\Database\Factories;

use Carbon\CarbonImmutable;
use Functional\Inspection\Models\Damage;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

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
            'reported_by' => fn () => Factory::factoryForModel($this->userModel()),
            'reported_at' => CarbonImmutable::now(),
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => [
            'resolved_by' => fn () => Factory::factoryForModel($this->userModel()),
            'resolved_at' => CarbonImmutable::now(),
        ]);
    }

    public function resolvedBy(Model $resolver): static
    {
        return $this->state(fn (): array => [
            'resolved_by' => $resolver->getKey(),
            'resolved_at' => CarbonImmutable::now(),
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
