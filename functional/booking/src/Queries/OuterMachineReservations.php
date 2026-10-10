<?php

namespace Functional\Booking\Queries;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\Eloquent\Builder;

final class OuterMachineReservations
{
    /**
     * @return Builder<Reservation>
     */
    public function overlapping(CarbonImmutable $startDate, CarbonImmutable $endDate): Builder
    {
        return $this->correlated()
            ->whereNot('status', ReservationStatus::Cancelled)
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate);
    }

    /**
     * @return Builder<Reservation>
     */
    public function overdue(): Builder
    {
        return $this->correlated()
            ->where('status', ReservationStatus::InProgress)
            ->whereDate('end_date', '<', CarbonImmutable::today());
    }

    /**
     * @return Builder<Reservation>
     */
    private function correlated(): Builder
    {
        return Reservation::query()->whereColumn(
            (new Reservation)->machine()->getQualifiedForeignKeyName(),
            (new Machine)->getQualifiedKeyName(),
        );
    }
}
