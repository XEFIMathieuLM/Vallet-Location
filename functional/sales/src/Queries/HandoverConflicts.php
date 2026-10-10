<?php

namespace Functional\Sales\Queries;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Machine;

final class HandoverConflicts
{
    public function firstFor(Machine $machine, CarbonImmutable $plannedHandoverDate): ?Reservation
    {
        return Reservation::query()
            ->with(['agency', 'customer'])
            ->whereBelongsTo($machine)
            ->whereIn('status', [ReservationStatus::Confirmed, ReservationStatus::InProgress])
            ->whereDate('end_date', '>=', $plannedHandoverDate)
            ->orderBy('start_date')
            ->first();
    }
}
