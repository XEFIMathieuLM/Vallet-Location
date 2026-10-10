<?php

namespace Functional\Booking\Queries;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;

final class AvailableMachinesQuery
{
    /**
     * @return Collection<int, Machine>
     */
    public function get(CarbonImmutable $startDate, CarbonImmutable $endDate, ?int $categoryId = null, ?int $agencyId = null): Collection
    {
        return Machine::query()
            ->with(['category', 'agency'])
            ->when($categoryId !== null, fn ($query) => $query->where('machine_category_id', $categoryId))
            ->when($agencyId !== null, fn ($query) => $query->where('agency_id', $agencyId))
            ->whereNotExists(fn (Builder $reservations) => $this->overlappingReservations($reservations, $startDate, $endDate))
            ->orderBy('reference')
            ->get();
    }

    private function overlappingReservations(Builder $reservations, CarbonImmutable $startDate, CarbonImmutable $endDate): Builder
    {
        return $reservations
            ->selectRaw('1')
            ->from('reservations')
            ->whereColumn('reservations.machine_id', 'machines.id')
            ->where('reservations.status', '<>', ReservationStatus::Cancelled->value)
            ->whereDate('reservations.start_date', '<=', $endDate)
            ->whereDate('reservations.end_date', '>=', $startDate);
    }
}
