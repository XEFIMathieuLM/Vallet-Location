<?php

namespace Functional\Booking\Database\Seeders;

use Functional\Booking\Actions\RefreshReservationConflicts;
use Functional\Booking\Database\Factories\ReservationFactory;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    private const CANCELLATION_EVERY = 3;

    private bool $isOverdueRentalSeeded = false;

    public function run(RefreshReservationConflicts $refreshReservationConflicts): void
    {
        $customers = Customer::query()->get();
        $employees = $this->employees();
        $machines = Machine::query()->orderBy('reference')->get();

        foreach ($machines as $position => $machine) {
            $this->seedHistoryOf($machine, $position, fn (): ReservationFactory => $this->reservationOf($machine, $customers->random(), $employees->random()));
        }

        $refreshReservationConflicts->handleMachines($machines);
    }

    /**
     * @param  callable(): ReservationFactory  $reservation
     */
    private function seedHistoryOf(Machine $machine, int $position, callable $reservation): void
    {
        $reservation()->closed()->create();

        if ($machine->status === MachineStatus::Retired) {
            return;
        }

        if ($machine->status === MachineStatus::RentedOut) {
            $this->seedCurrentRental($reservation);
        }

        $reservation()->upcoming()->create();

        if ($position % self::CANCELLATION_EVERY === 0) {
            $reservation()->cancelled()->create();
        }
    }

    /**
     * @param  callable(): ReservationFactory  $reservation
     */
    private function seedCurrentRental(callable $reservation): void
    {
        if ($this->isOverdueRentalSeeded) {
            $reservation()->ongoing()->create();

            return;
        }

        $reservation()->overdue()->create();
        $this->isOverdueRentalSeeded = true;
    }

    private function reservationOf(Machine $machine, Customer $customer, Model&AgencyMember $employee): ReservationFactory
    {
        return Reservation::factory()
            ->for($machine)
            ->for($customer)
            ->state(['agency_id' => $employee->agencyId(), 'created_by' => $employee->getKey()]);
    }

    /**
     * @return Collection<int, Model&AgencyMember>
     */
    private function employees(): Collection
    {
        /** @var class-string<Model&AgencyMember> $userModel */
        $userModel = config('auth.providers.users.model');

        return $userModel::query()->get();
    }
}
