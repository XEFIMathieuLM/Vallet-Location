<?php

namespace Functional\Billing\Queries;

use Carbon\CarbonImmutable;
use Functional\Billing\Enums\DamageOutcome;
use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Models\BillablePeriod;
use Functional\Billing\Models\DamageSettlement;
use Functional\Billing\Money\Money;
use Functional\Billing\ValueObjects\StatementFigures;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Models\Damage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class BillingStatement
{
    public function for(?int $agencyId, CarbonImmutable $firstDay, CarbonImmutable $lastDay): StatementFigures
    {
        $periodBounds = [$firstDay->startOfDay(), $lastDay->endOfDay()];

        return new StatementFigures(
            transmittedRentalsCount: BillablePeriod::query()
                ->whereBetween('end_date', [$firstDay->toDateString(), $lastDay->toDateString()])
                ->whereExists(fn (QueryBuilder $transmissions) => $transmissions
                    ->selectRaw('1')
                    ->from('transmissions')
                    ->whereColumn('transmissions.billable_period_id', 'billable_periods.id')
                    ->whereIn('transmissions.status', [TransmissionStatus::Sent->value, TransmissionStatus::Exported->value]))
                ->when($agencyId !== null, fn (Builder $periods) => $periods->whereIn('reservation_id', $this->agencyReservationIds((int) $agencyId)))
                ->distinct()
                ->count('reservation_id'),
            billedDamagesTotal: Money::fromStored((int) $this->settlements(DamageOutcome::Billed, $agencyId, $periodBounds)->sum('amount_cents')),
            waivedDamages: $this->settlements(DamageOutcome::Waived, $agencyId, $periodBounds)
                ->with(['damage.view', 'damage.reservation.machine', 'settler'])
                ->orderBy('settled_at')
                ->get(),
            unresolvedDamages: Damage::query()
                ->whereNull('resolved_at')
                ->where('reported_at', '<=', $periodBounds[1])
                ->when($agencyId !== null, fn (Builder $damages) => $damages->whereIn('reservation_id', $this->agencyReservationIds((int) $agencyId)))
                ->with(['view', 'reservation.machine'])
                ->orderBy('reported_at')
                ->get(),
        );
    }

    /**
     * @param  array{CarbonImmutable, CarbonImmutable}  $periodBounds
     * @return Builder<DamageSettlement>
     */
    private function settlements(DamageOutcome $outcome, ?int $agencyId, array $periodBounds): Builder
    {
        return DamageSettlement::query()
            ->where('outcome', $outcome)
            ->whereBetween('settled_at', $periodBounds)
            ->when($agencyId !== null, fn (Builder $settlements) => $settlements->whereIn(
                'damage_id',
                Damage::query()->select('id')->whereIn('reservation_id', $this->agencyReservationIds((int) $agencyId)),
            ));
    }

    /**
     * @return Builder<Reservation>
     */
    private function agencyReservationIds(int $agencyId): Builder
    {
        return Reservation::query()
            ->select('reservations.id')
            ->join('machines', 'machines.id', '=', 'reservations.machine_id')
            ->where('machines.agency_id', $agencyId);
    }
}
