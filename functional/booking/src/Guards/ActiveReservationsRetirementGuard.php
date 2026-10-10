<?php

namespace Functional\Booking\Guards;

use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Contracts\MachineRetirementGuard;
use Functional\Fleet\Exceptions\MachineRetirementRefusedException;
use Functional\Fleet\Models\Machine;

final class ActiveReservationsRetirementGuard implements MachineRetirementGuard
{
    public function ensureCanRetire(Machine $machine): void
    {
        $activeReservationCount = Reservation::query()
            ->where('machine_id', $machine->id)
            ->whereIn('status', [ReservationStatus::Confirmed, ReservationStatus::InProgress])
            ->count();

        if ($activeReservationCount > 0) {
            throw MachineRetirementRefusedException::becauseOfActiveReservations($machine, $activeReservationCount);
        }
    }
}
