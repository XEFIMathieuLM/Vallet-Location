<?php

namespace Functional\Deposit\Actions;

use Carbon\CarbonImmutable;
use Functional\Billing\Money\Money;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Deposit\Enums\DepositHistoryEvent;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Events\DepositChanged;
use Functional\Deposit\Exceptions\DepositRefusedException;
use Functional\Deposit\History\DepositHistory;
use Functional\Deposit\Models\Deposit;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Inspection\Actions\CountUnresolvedDamages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class RefundDeposit
{
    public function __construct(
        private readonly SyncDepositStatus $syncDepositStatus,
        private readonly CountUnresolvedDamages $countUnresolvedDamages,
        private readonly DepositHistory $depositHistory,
    ) {}

    public function handle(Deposit $deposit, bool $isNoDamageConfirmed, Model&AgencyMember $author): Deposit
    {
        $this->syncDepositStatus->for($deposit->reservation);

        $refundedDeposit = DB::transaction(function () use ($deposit, $isNoDamageConfirmed, $author): Deposit {
            $lockedDeposit = Deposit::query()->with('reservation')->lockForUpdate()->findOrFail($deposit->id);
            $isAfterReturn = $lockedDeposit->reservation->status === ReservationStatus::Closed;
            $this->ensureRefundable($lockedDeposit, $isAfterReturn && ! $isNoDamageConfirmed);

            $lockedDeposit->update([
                'status' => $lockedDeposit->state()->refund()->status(),
                'retained' => Money::zero(),
                'refunded' => $lockedDeposit->amount,
                'is_no_damage_confirmed' => $isAfterReturn && $isNoDamageConfirmed,
                'closed_by' => $author->getKey(),
                'closed_agency_id' => $author->agencyId(),
                'closed_at' => CarbonImmutable::now(),
            ]);

            $this->depositHistory->record($lockedDeposit->reservation, DepositHistoryEvent::Refunded, $author, [
                'amount' => $lockedDeposit->amount->format(),
                'is_no_damage_confirmed' => $lockedDeposit->is_no_damage_confirmed,
            ]);

            return $lockedDeposit;
        });

        DepositChanged::dispatch($refundedDeposit->reservation_id);

        return $refundedDeposit;
    }

    private function ensureRefundable(Deposit $deposit, bool $isConfirmationMissing): void
    {
        match (true) {
            $deposit->status->isFinal() => throw DepositRefusedException::alreadyClosed(),
            $deposit->status === DepositStatus::BlockedByDamage => throw DepositRefusedException::damagesToSettle($this->countUnresolvedDamages->for($deposit->reservation)),
            $deposit->status !== DepositStatus::ToRefund => throw DepositRefusedException::notRefundable($deposit->status),
            $isConfirmationMissing => throw DepositRefusedException::noDamageNotConfirmed(),
            default => null,
        };
    }
}
