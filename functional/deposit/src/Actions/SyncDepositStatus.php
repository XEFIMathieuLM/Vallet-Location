<?php

namespace Functional\Deposit\Actions;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Deposit\Enums\DepositHistoryEvent;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Events\DepositChanged;
use Functional\Deposit\History\DepositHistory;
use Functional\Deposit\Models\Deposit;
use Functional\Deposit\Queries\BilledDamagesTotal;
use Functional\Deposit\States\DepositState;
use Functional\Inspection\Actions\CountUnresolvedDamages;
use Illuminate\Support\Facades\DB;

final class SyncDepositStatus
{
    public function __construct(
        private readonly CountUnresolvedDamages $countUnresolvedDamages,
        private readonly BilledDamagesTotal $billedDamagesTotal,
        private readonly DepositHistory $depositHistory,
    ) {}

    public function for(Reservation $reservation): void
    {
        $isChanged = DB::transaction(function () use ($reservation): bool {
            $deposit = Deposit::query()->whereBelongsTo($reservation)->lockForUpdate()->first();

            if ($deposit === null || $deposit->status->isFinal()) {
                return false;
            }

            $dueStatus = $this->dueStatus($deposit, $reservation->refresh());

            if ($dueStatus === null || $dueStatus === $deposit->status) {
                return false;
            }

            $deposit->update([
                'status' => $this->transitionTo($deposit->state(), $dueStatus)->status(),
                'awaiting_since' => CarbonImmutable::now(),
            ]);
            $this->recordTransition($reservation, $dueStatus);

            return true;
        });

        if ($isChanged) {
            DepositChanged::dispatch($reservation->id);
        }
    }

    private function dueStatus(Deposit $deposit, Reservation $reservation): ?DepositStatus
    {
        if ($reservation->status === ReservationStatus::Cancelled) {
            return DepositStatus::ToRefund;
        }

        if ($reservation->status !== ReservationStatus::Closed) {
            return null;
        }

        if ($this->countUnresolvedDamages->for($reservation) > 0) {
            return DepositStatus::BlockedByDamage;
        }

        return $this->billedDamagesTotal->for($reservation)->isPositive() ? DepositStatus::ToSettle : DepositStatus::ToRefund;
    }

    private function transitionTo(DepositState $state, DepositStatus $dueStatus): DepositState
    {
        return match ($dueStatus) {
            DepositStatus::ToRefund => $state->toRefund(),
            DepositStatus::BlockedByDamage => $state->blockByDamage(),
            DepositStatus::ToSettle => in_array($state->status(), [DepositStatus::Collected, DepositStatus::ToRefund], true)
                ? $state->blockByDamage()->toSettle()
                : $state->toSettle(),
            default => $state,
        };
    }

    private function recordTransition(Reservation $reservation, DepositStatus $dueStatus): void
    {
        $event = $dueStatus === DepositStatus::BlockedByDamage ? DepositHistoryEvent::BlockedByDamage : DepositHistoryEvent::Released;

        $this->depositHistory->recordSystem($reservation, $event, ['status' => mb_strtolower($dueStatus->label())]);
    }
}
