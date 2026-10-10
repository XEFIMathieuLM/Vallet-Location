<?php

namespace Functional\Booking\Actions;

use Functional\Booking\Events\ReservationChanged;
use Functional\Booking\Models\Reservation;
use Illuminate\Support\Facades\DB;

final class CancelReservation
{
    public function handle(Reservation $reservation): Reservation
    {
        DB::transaction(function () use ($reservation): void {
            $lockedReservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);

            $lockedReservation->update([
                'status' => $lockedReservation->state()->cancel()->status(),
                'conflict_reason' => null,
            ]);
        });

        $reservation->refresh();
        ReservationChanged::dispatch($reservation);

        return $reservation;
    }
}
