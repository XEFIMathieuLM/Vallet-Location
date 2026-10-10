<?php

namespace Functional\Inspection\Completeness;

use Functional\Booking\Models\Reservation;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Models\ReservationView;

class ViewCompleteness
{
    public function for(Reservation $reservation): StepCompleteness
    {
        $countsQuery = ReservationView::query()
            ->whereBelongsTo($reservation)
            ->toBase()
            ->selectRaw('count(*) as views_count');

        foreach (InspectionStep::cases() as $step) {
            $countsQuery->selectRaw(
                "count(*) filter (where not exists (select 1 from photos where photos.reservation_view_id = reservation_views.id and photos.step = ?)) as missing_{$step->value}",
                [$step->value],
            );
        }

        $counts = (array) $countsQuery->first();

        return new StepCompleteness(
            (int) $counts['views_count'],
            collect(InspectionStep::cases())
                ->mapWithKeys(fn (InspectionStep $step): array => [$step->value => (int) $counts["missing_{$step->value}"]])
                ->all(),
        );
    }
}
