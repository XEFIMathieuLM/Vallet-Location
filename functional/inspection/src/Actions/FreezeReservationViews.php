<?php

namespace Functional\Inspection\Actions;

use Functional\Booking\Models\Reservation;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Support\Facades\DB;

class FreezeReservationViews
{
    public function __construct(private readonly ResolveRequiredViews $resolveRequiredViews) {}

    public function handle(Reservation $reservation): void
    {
        DB::transaction(function () use ($reservation): void {
            Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            if (ReservationView::query()->where('reservation_id', $reservation->id)->exists()) {
                return;
            }

            foreach ($this->resolveRequiredViews->forReservation($reservation) as $offset => $label) {
                ReservationView::query()->create([
                    'reservation_id' => $reservation->id,
                    'label' => $label,
                    'position' => $offset + 1,
                ]);
            }
        });
    }
}
