<?php

namespace Functional\Booking\Actions;

use Carbon\CarbonImmutable;
use Functional\Booking\Events\ReservationChanged;
use Functional\Booking\Exceptions\DepartureRefusedException;
use Functional\Booking\Extensions\ReservationTransitionGuards;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Actions\ChangeMachineStatus;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Enums\MachineTransition;
use Functional\Fleet\Models\Machine;
use Illuminate\Support\Facades\DB;

final class DepartReservation
{
    public function __construct(
        private readonly ChangeMachineStatus $changeMachineStatus,
        private readonly ReservationTransitionGuards $transitionGuards,
    ) {}

    public function handle(Reservation $reservation): Reservation
    {
        DB::transaction(function () use ($reservation): void {
            $lockedReservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);
            $nextState = $lockedReservation->state()->depart();
            $machine = $lockedReservation->machine()->lockForUpdate()->firstOrFail();

            $this->ensureDepartureIsAllowed($lockedReservation, $machine);

            foreach ($this->transitionGuards->all() as $transitionGuard) {
                $transitionGuard->beforeDeparture($lockedReservation);
            }

            $lockedReservation->update(['status' => $nextState->status(), 'departed_at' => CarbonImmutable::now()]);
            $this->changeMachineStatus->handle($machine, MachineTransition::Depart);
        });

        $reservation->refresh();
        ReservationChanged::dispatch($reservation);

        return $reservation;
    }

    private function ensureDepartureIsAllowed(Reservation $reservation, Machine $machine): void
    {
        if (CarbonImmutable::today()->lt($reservation->start_date)) {
            throw DepartureRefusedException::beforeStartDate($reservation->start_date);
        }

        if ($machine->status !== MachineStatus::Available) {
            throw DepartureRefusedException::becauseOfMachineStatus($machine);
        }

        if (! $machine->isVgpCompliantUntil($reservation->end_date)) {
            throw DepartureRefusedException::becauseOfVgp($machine);
        }
    }
}
