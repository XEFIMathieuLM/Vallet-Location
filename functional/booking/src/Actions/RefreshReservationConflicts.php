<?php

namespace Functional\Booking\Actions;

use Functional\Booking\Eligibility\MachineEligibility;
use Functional\Booking\Enums\ConflictReason;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Events\ReservationChanged;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Machine;

final class RefreshReservationConflicts
{
    public function __construct(private readonly MachineEligibility $machineEligibility) {}

    public function handle(Machine $machine): void
    {
        $upcomingReservations = Reservation::query()
            ->where('machine_id', $machine->id)
            ->where('status', ReservationStatus::Confirmed)
            ->orderBy('start_date')
            ->get();
        $isOverdue = $this->machineEligibility->isOverdue($machine);

        foreach ($upcomingReservations as $position => $reservation) {
            $conflictReason = $this->conflictReason($machine, $reservation, $isOverdue && $position === 0);

            if ($reservation->conflict_reason === $conflictReason) {
                continue;
            }

            $reservation->update(['conflict_reason' => $conflictReason]);
            ReservationChanged::dispatch($reservation);
        }
    }

    private function conflictReason(Machine $machine, Reservation $reservation, bool $isWaitingForOverdueMachine): ?ConflictReason
    {
        return match (true) {
            ! $machine->state()->acceptsReservations() => ConflictReason::MachineUnavailable,
            $isWaitingForOverdueMachine => ConflictReason::MachineNotReturned,
            ! $machine->isVgpCompliantUntil($reservation->end_date) => ConflictReason::VgpExpired,
            default => null,
        };
    }
}
