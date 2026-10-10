<?php

namespace Functional\Inspection\Database\Factories;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Models\PhotoSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PhotoSession>
 */
class PhotoSessionFactory extends Factory
{
    protected $model = PhotoSession::class;

    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'step' => InspectionStep::Departure,
            'token_hash' => PhotoSession::hashToken(Str::random(40)),
            'created_by' => User::factory(),
            'expires_at' => CarbonImmutable::now()->addMinutes(30),
        ];
    }

    public function forStep(InspectionStep $step): static
    {
        return $this->state(fn (): array => ['step' => $step]);
    }

    public function withToken(string $token): static
    {
        return $this->state(fn (): array => ['token_hash' => PhotoSession::hashToken($token)]);
    }
}
