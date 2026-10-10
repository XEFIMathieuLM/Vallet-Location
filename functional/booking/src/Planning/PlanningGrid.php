<?php

namespace Functional\Booking\Planning;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriodImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class PlanningGrid
{
    /**
     * @param  list<CarbonImmutable>  $days
     * @param  list<PlanningRow>  $rows
     */
    private function __construct(
        public array $days,
        public array $rows,
    ) {}

    /**
     * @return Builder<Machine>
     */
    public static function machinesQuery(?int $categoryId, ?int $agencyId): Builder
    {
        return Machine::query()
            ->with(['category', 'agency'])
            ->whereNot('status', MachineStatus::Retired)
            ->when($categoryId !== null, fn ($query) => $query->where('machine_category_id', $categoryId))
            ->when($agencyId !== null, fn ($query) => $query->where('agency_id', $agencyId))
            ->orderBy('reference');
    }

    /**
     * @param  iterable<Machine>  $machines
     */
    public static function build(iterable $machines, CarbonImmutable $startDate, CarbonImmutable $endDate): self
    {
        $machines = collect($machines);
        $days = array_values(iterator_to_array(CarbonPeriodImmutable::create($startDate->startOfDay(), $endDate->startOfDay())));
        $reservationsByMachine = Reservation::query()
            ->with('customer')
            ->whereIn('machine_id', $machines->map(fn (Machine $machine): int => $machine->id)->all())
            ->whereIn('status', [ReservationStatus::Confirmed, ReservationStatus::InProgress])
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->get()
            ->groupBy('machine_id');

        $rows = $machines->map(fn (Machine $machine): PlanningRow => new PlanningRow(
            $machine,
            self::cells($machine, $days, $reservationsByMachine->get($machine->id, collect())),
        ));

        return new self($days, $rows->values()->all());
    }

    /**
     * @param  list<CarbonImmutable>  $days
     * @param  Collection<int, Reservation>  $reservations
     * @return array<string, PlanningCell>
     */
    private static function cells(Machine $machine, array $days, Collection $reservations): array
    {
        $today = CarbonImmutable::today();
        $cells = [];

        foreach ($days as $day) {
            $reservation = $reservations->first(fn (Reservation $candidate): bool => $day->betweenIncluded($candidate->start_date, $candidate->end_date));

            $cells[$day->toDateString()] = match (true) {
                $reservation !== null => new PlanningCell(PlanningCellKind::Reserved, $reservation),
                $day->gte($today) && $machine->status === MachineStatus::Workshop => new PlanningCell(PlanningCellKind::Workshop),
                $day->gte($today) && $machine->status === MachineStatus::OutOfOrder => new PlanningCell(PlanningCellKind::OutOfOrder),
                ! $machine->isVgpCompliantUntil($day) => new PlanningCell(PlanningCellKind::VgpInvalid),
                default => new PlanningCell(PlanningCellKind::Free),
            };
        }

        return $cells;
    }
}
