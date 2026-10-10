<?php

namespace Functional\Deposit\Console;

use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Deposit\Actions\SyncDepositStatus;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Models\Deposit;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class ReconcileDeposits extends Command
{
    protected $signature = 'deposit:reconcile';

    protected $description = 'Bring every open deposit in line with its reservation and its damages';

    public function handle(SyncDepositStatus $syncDepositStatus): int
    {
        Reservation::query()
            ->whereIn('status', [ReservationStatus::Closed->value, ReservationStatus::Cancelled->value])
            ->whereIn('id', Deposit::query()->select('reservation_id')->whereNotIn('status', [DepositStatus::Refunded->value, DepositStatus::Settled->value]))
            ->chunkById(100, function (Collection $reservations) use ($syncDepositStatus): void {
                $reservations->each(fn (Reservation $reservation) => $syncDepositStatus->for($reservation));
            });

        return self::SUCCESS;
    }
}
