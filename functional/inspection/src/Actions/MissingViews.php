<?php

namespace Functional\Inspection\Actions;

use Functional\Booking\Models\Reservation;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class MissingViews
{
    /**
     * @return Collection<int, ReservationView>
     */
    public function for(Reservation $reservation, InspectionStep $step): Collection
    {
        return ReservationView::query()
            ->whereBelongsTo($reservation)
            ->whereDoesntHave('photos', fn (Builder $photos): Builder => $photos->where('step', $step))
            ->orderBy('position')
            ->get();
    }
}
