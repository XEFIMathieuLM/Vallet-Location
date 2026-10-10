<?php

namespace Functional\Booking\Eligibility;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Exceptions\MachineNotReservableException;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Machine;

final class MachineEligibility
{
    public function ensureReservableUntil(Machine $machine, CarbonImmutable $endDate): void
    {
        if (! $machine->state()->acceptsReservations()) {
            throw MachineNotReservableException::becauseOfStatus($machine);
        }

        if ($this->isOverdue($machine)) {
            throw MachineNotReservableException::becauseNotReturned($machine);
        }

        if (! $machine->isVgpCompliantUntil($endDate)) {
            throw MachineNotReservableException::becauseOfVgp($machine);
        }
    }

    public function isOverdue(Machine $machine): bool
    {
        return Reservation::query()
            ->where('machine_id', $machine->id)
            ->where('status', ReservationStatus::InProgress)
            ->whereDate('end_date', '<', CarbonImmutable::today())
            ->exists();
    }
}
