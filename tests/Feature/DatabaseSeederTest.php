<?php

namespace Tests\Feature;

use App\Models\User;
use Functional\Booking\Enums\ConflictReason;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_demo_data_covers_every_status_and_conflict_reason(): void
    {
        $this->seed();

        $this->assertSame(7, Agency::query()->count());
        $this->assertSame(Agency::query()->count(), User::query()->count());
        $this->assertTrue(Customer::query()->exists());

        foreach (MachineStatus::cases() as $machineStatus) {
            $this->assertTrue(Machine::query()->where('status', $machineStatus)->exists(), "machine {$machineStatus->value}");
        }

        foreach (ReservationStatus::cases() as $reservationStatus) {
            $this->assertTrue(Reservation::query()->where('status', $reservationStatus)->exists(), "reservation {$reservationStatus->value}");
        }

        foreach (ConflictReason::cases() as $conflictReason) {
            $this->assertTrue(Reservation::query()->where('conflict_reason', $conflictReason)->exists(), "conflict {$conflictReason->value}");
        }
    }

    public function test_every_rented_out_machine_has_a_reservation_in_progress(): void
    {
        $this->seed();

        Machine::query()->where('status', MachineStatus::RentedOut)->each(function (Machine $machine): void {
            $this->assertTrue(
                Reservation::query()->whereBelongsTo($machine)->where('status', ReservationStatus::InProgress)->exists(),
                "machine {$machine->reference}",
            );
        });
    }
}
