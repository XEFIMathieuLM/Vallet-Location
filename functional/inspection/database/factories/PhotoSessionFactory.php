<?php

namespace Functional\Inspection\Database\Factories;

use Carbon\CarbonImmutable;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Enums\RevocationReason;
use Functional\Inspection\Models\PhotoSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

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
            'token_hash' => faker()->inspectionTokenHash(),
            'created_by' => fn () => Factory::factoryForModel($this->userModel()),
            'expires_at' => CarbonImmutable::now()->addMinutes(30),
        ];
    }

    public function forStep(InspectionStep $step): static
    {
        return $this->state(fn (): array => ['step' => $step]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['expires_at' => CarbonImmutable::now()->subMinutes(faker()->number(31, 600))]);
    }

    public function revoked(RevocationReason $reason): static
    {
        return $this->state(fn (): array => [
            'revoked_at' => CarbonImmutable::now()->subMinutes(faker()->number(1, 29)),
            'revoked_reason' => $reason,
        ]);
    }

    public function withToken(string $token): static
    {
        return $this->state(fn (): array => ['token_hash' => PhotoSession::hashToken($token)]);
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        return config('auth.providers.users.model');
    }
}
