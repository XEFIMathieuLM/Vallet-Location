<?php

namespace Functional\Booking\Actions;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReturnCondition;
use Functional\Booking\Events\ReservationChanged;
use Functional\Booking\Extensions\ReservationTransitionGuards;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Actions\ChangeMachineStatus;
use Illuminate\Support\Facades\DB;

final class ReturnReservation
{
    public function __construct(
        private readonly ChangeMachineStatus $changeMachineStatus,
        private readonly ReservationTransitionGuards $transitionGuards,
    ) {}

    public function handle(Reservation $reservation, ReturnCondition $returnCondition): Reservation
    {
        DB::transaction(function () use ($reservation, $returnCondition): void {
            $lockedReservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);
            $nextState = $lockedReservation->state()->returnMachine();

            foreach ($this->transitionGuards->all() as $transitionGuard) {
                $transitionGuard->beforeReturn($lockedReservation);
            }

            $machine = $lockedReservation->machine()->lockForUpdate()->firstOrFail();
            $returnDate = CarbonImmutable::today();

            $lockedReservation->update([
                'status' => $nextState->status(),
                'returned_at' => CarbonImmutable::now(),
                'end_date' => $returnDate->lt($lockedReservation->end_date) ? $returnDate : $lockedReservation->end_date,
            ]);
            $this->changeMachineStatus->handle($machine, $returnCondition->machineTransition());
        });

        $reservation->refresh();
        ReservationChanged::dispatch($reservation);

        return $reservation;
    }
}
