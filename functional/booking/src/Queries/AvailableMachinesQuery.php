<?php

namespace Functional\Booking\Queries;

use Carbon\CarbonImmutable;
use Functional\Booking\Extensions\ReservationRequestGuards;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Illuminate\Database\Eloquent\Collection;

final class AvailableMachinesQuery
{
    public function __construct(
        private readonly OuterMachineReservations $outerMachineReservations,
        private readonly ReservationRequestGuards $reservationRequestGuards,
    ) {}

    /**
     * @return Collection<int, Machine>
     */
    public function get(CarbonImmutable $startDate, CarbonImmutable $endDate, ?MachineCategory $category = null, ?Agency $agency = null): Collection
    {
        $machines = Machine::query()
            ->with(['category', 'agency'])
            ->when($category, fn ($query, MachineCategory $category) => $query->whereBelongsTo($category, 'category'))
            ->when($agency, fn ($query, Agency $agency) => $query->whereBelongsTo($agency))
            ->whereIn('status', [MachineStatus::Available, MachineStatus::RentedOut])
            ->where(fn ($vgp) => $vgp->where('is_subject_to_vgp', false)->orWhereDate('vgp_due_date', '>=', $endDate))
            ->whereNotExists($this->outerMachineReservations->overlapping($startDate, $endDate))
            ->whereNotExists($this->outerMachineReservations->overdue())
            ->orderBy('reference');

        foreach ($this->reservationRequestGuards->all() as $reservationRequestGuard) {
            $reservationRequestGuard->excludeUnavailable($machines, $startDate, $endDate);
        }

        return $machines->get();
    }
}
