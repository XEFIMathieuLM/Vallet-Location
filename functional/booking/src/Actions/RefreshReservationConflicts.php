<?php

namespace Functional\Booking\Actions;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ConflictReason;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Events\ReservationChanged;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\Eloquent\Collection;

final class RefreshReservationConflicts
{
    public function handle(Machine $machine): void
    {
        $this->handleMachines(new Collection([$machine]));
    }

    /**
     * @param  Collection<int, Machine>  $machines
     */
    public function handleMachines(Collection $machines): void
    {
        $machinesById = $machines->keyBy('id');
        $upcomingReservations = Reservation::query()
            ->whereIn('machine_id', $machinesById->keys())
            ->where('status', ReservationStatus::Confirmed)
            ->orderBy('machine_id')
            ->orderBy('start_date')
            ->get();
        $overdueMachineIds = Reservation::query()
            ->whereIn('machine_id', $machinesById->keys())
            ->where('status', ReservationStatus::InProgress)
            ->whereDate('end_date', '<', CarbonImmutable::today())
            ->pluck('machine_id')
            ->flip();

        $upcomingReservations->groupBy('machine_id')->each(function (Collection $machineReservations, int $machineId) use ($machinesById, $overdueMachineIds): void {
            foreach ($machineReservations->values() as $position => $reservation) {
                $isWaitingForOverdueMachine = $position === 0 && $overdueMachineIds->has($machineId);
                $this->applyConflictReason($reservation, $this->conflictReason($machinesById[$machineId], $reservation, $isWaitingForOverdueMachine));
            }
        });
    }

    private function applyConflictReason(Reservation $reservation, ?ConflictReason $conflictReason): void
    {
        if ($reservation->conflict_reason === $conflictReason) {
            return;
        }

        $reservation->update(['conflict_reason' => $conflictReason]);
        ReservationChanged::dispatch($reservation);
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
