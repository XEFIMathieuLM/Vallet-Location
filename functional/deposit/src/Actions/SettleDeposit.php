<?php

namespace Functional\Deposit\Actions;

use Carbon\CarbonImmutable;
use Functional\Deposit\Enums\DepositHistoryEvent;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Events\DepositChanged;
use Functional\Deposit\Exceptions\DepositRefusedException;
use Functional\Deposit\History\DepositHistory;
use Functional\Deposit\Models\Deposit;
use Functional\Deposit\Queries\BilledDamagesTotal;
use Functional\Deposit\ValueObjects\DepositRetention;
use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class SettleDeposit
{
    public function __construct(
        private readonly SyncDepositStatus $syncDepositStatus,
        private readonly BilledDamagesTotal $billedDamagesTotal,
        private readonly DepositHistory $depositHistory,
    ) {}

    public function handle(Deposit $deposit, Model&AgencyMember $author): Deposit
    {
        $this->syncDepositStatus->for($deposit->reservation);

        $settledDeposit = DB::transaction(function () use ($deposit, $author): Deposit {
            $lockedDeposit = Deposit::query()->with('reservation')->lockForUpdate()->findOrFail($deposit->id);

            if ($lockedDeposit->status !== DepositStatus::ToSettle) {
                throw $lockedDeposit->status->isFinal() ? DepositRefusedException::alreadyClosed() : DepositRefusedException::notSettleable($lockedDeposit->status);
            }

            $retention = DepositRetention::fromBilledTotal($lockedDeposit->amount, $this->billedDamagesTotal->for($lockedDeposit->reservation));

            $lockedDeposit->update([
                'status' => $lockedDeposit->state()->settle()->status(),
                'retained' => $retention->retained,
                'refunded' => $retention->refunded,
                'closed_by' => $author->getKey(),
                'closed_agency_id' => $author->agencyId(),
                'closed_at' => CarbonImmutable::now(),
            ]);

            $this->depositHistory->record($lockedDeposit->reservation, DepositHistoryEvent::Settled, $author, [
                'retained' => $retention->retained->format(),
                'refunded' => $retention->refunded->format(),
            ]);

            return $lockedDeposit;
        });

        DepositChanged::dispatch($settledDeposit->reservation_id);

        return $settledDeposit;
    }
}
