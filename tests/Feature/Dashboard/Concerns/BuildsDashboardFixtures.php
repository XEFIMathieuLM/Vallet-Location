<?php

namespace Tests\Feature\Dashboard\Concerns;

use App\Models\User;
use BackedEnum;
use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;

trait BuildsDashboardFixtures
{
    private ?MachineCategory $sharedCategory = null;

    protected function sharedCategory(): MachineCategory
    {
        return $this->sharedCategory ??= MachineCategory::factory()->create();
    }

    protected function agencyNamed(string $name): Agency
    {
        return Agency::factory()->create(['name' => $name]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function machineIn(Agency $agency, array $attributes = []): Machine
    {
        return Machine::factory()->for($agency)->recycle($this->sharedCategory())->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function reservationOf(Machine $machine, ReservationStatus $status, string $start, string $end, array $attributes = []): Reservation
    {
        $startDate = CarbonImmutable::parse($start);

        return Reservation::factory()
            ->for($machine)
            ->recycle($machine->agency)
            ->between($startDate, CarbonImmutable::parse($end))
            ->withStatus($status)
            ->create([
                'departed_at' => in_array($status, [ReservationStatus::InProgress, ReservationStatus::Closed], true) ? $startDate : null,
                ...$attributes,
            ]);
    }

    protected function employeeOf(Agency $agency): User
    {
        return User::factory()->employee()->for($agency)->create();
    }

    protected function memberOf(Agency $agency, BackedEnum ...$permissions): User
    {
        $user = User::factory()->for($agency)->create();
        $user->givePermissionTo(array_map(fn (BackedEnum $permission): string|int => $permission->value, $permissions));

        return $user;
    }
}
