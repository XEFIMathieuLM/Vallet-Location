<?php

namespace Functional\Deposit\Queries;

use Carbon\CarbonImmutable;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Models\Deposit;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\Eloquent\Builder;

final class PendingDeposits
{
    private const OVERDUE_STATUSES = [DepositStatus::ToRefund, DepositStatus::ToSettle];

    /**
     * @return Builder<Deposit>
     */
    public function query(?int $agencyId = null, ?DepositStatus $status = null): Builder
    {
        $awaitingStatuses = array_filter(DepositStatus::cases(), fn (DepositStatus $depositStatus): bool => $depositStatus->isAwaitingAction());

        return Deposit::query()
            ->whereIn('status', array_map(fn (DepositStatus $awaitingStatus): string => $awaitingStatus->value, $status !== null ? [$status] : $awaitingStatuses))
            ->when($agencyId !== null, fn (Builder $query): Builder => $query->whereHas('reservation', fn (Builder $reservations): Builder => $reservations->whereIn('machine_id', Machine::query()->select('id')->where('agency_id', $agencyId))))
            ->with(['reservation.customer', 'reservation.machine.agency'])
            ->orderBy('awaiting_since');
    }

    public function isOverdue(Deposit $deposit): bool
    {
        if (! in_array($deposit->status, self::OVERDUE_STATUSES, true) || $deposit->awaiting_since === null) {
            return false;
        }

        return $deposit->awaiting_since->lt(CarbonImmutable::now()->subDays(config()->integer('deposit.overdue_after_days')));
    }
}
