<?php

namespace Functional\Booking\Queries;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\Eloquent\Builder;

final class DayOperations
{
    /**
     * @return Builder<Reservation>
     */
    public function departures(?int $homeAgencyId, CarbonImmutable $today): Builder
    {
        return $this->ofHomeAgency($homeAgencyId)
            ->where('status', ReservationStatus::Confirmed)
            ->whereDate('start_date', '<=', $today)
            ->orderBy('start_date')
            ->orderBy($this->machineReference());
    }

    /**
     * @return Builder<Reservation>
     */
    public function upcomingDepartures(?int $homeAgencyId, CarbonImmutable $today, int $days): Builder
    {
        return $this->ofHomeAgency($homeAgencyId)
            ->where('status', ReservationStatus::Confirmed)
            ->whereDate('start_date', '>=', $today->addDay())
            ->whereDate('start_date', '<=', $today->addDays($days))
            ->orderBy('start_date')
            ->orderBy($this->machineReference());
    }

    /**
     * @return Builder<Reservation>
     */
    public function returns(?int $homeAgencyId, CarbonImmutable $today): Builder
    {
        return $this->ofHomeAgency($homeAgencyId)
            ->where('status', ReservationStatus::InProgress)
            ->whereDate('end_date', $today)
            ->orderBy($this->machineReference());
    }

    /**
     * @return Builder<Reservation>
     */
    public function lateReturns(?int $homeAgencyId, CarbonImmutable $today): Builder
    {
        return $this->ofHomeAgency($homeAgencyId)
            ->where('status', ReservationStatus::InProgress)
            ->whereDate('end_date', '<', $today)
            ->orderBy('end_date')
            ->orderBy('id');
    }

    /**
     * @return Builder<Reservation>
     */
    public function conflicts(?int $homeAgencyId): Builder
    {
        return $this->ofHomeAgency($homeAgencyId)
            ->where('status', ReservationStatus::Confirmed)
            ->whereNotNull('conflict_reason')
            ->orderBy('start_date')
            ->orderBy('id');
    }

    /**
     * @return Builder<Reservation>
     */
    private function ofHomeAgency(?int $homeAgencyId): Builder
    {
        return Reservation::query()
            ->with(['machine.category', 'machine.agency', 'customer'])
            ->when($homeAgencyId !== null, fn (Builder $reservations): Builder => $reservations->whereIn(
                'machine_id',
                Machine::query()->select('id')->where('agency_id', $homeAgencyId),
            ));
    }

    /**
     * @return Builder<Machine>
     */
    private function machineReference(): Builder
    {
        return Machine::query()->select('reference')->whereColumn('machines.id', 'reservations.machine_id');
    }
}
