<?php

namespace Functional\Inspection\Actions;

use Carbon\CarbonImmutable;
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

            if (ReservationView::query()->whereBelongsTo($reservation)->exists()) {
                return;
            }

            $now = CarbonImmutable::now();
            $labels = $this->resolveRequiredViews->forReservation($reservation);

            ReservationView::query()->insert(array_map(
                fn (string $label, int $offset): array => [
                    'reservation_id' => $reservation->id,
                    'label' => $label,
                    'position' => $offset + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $labels,
                array_keys($labels),
            ));
        });
    }
}
