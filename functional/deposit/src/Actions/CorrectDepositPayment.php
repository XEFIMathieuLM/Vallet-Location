<?php

namespace Functional\Deposit\Actions;

use Functional\Deposit\Enums\DepositHistoryEvent;
use Functional\Deposit\Enums\PaymentMethod;
use Functional\Deposit\Events\DepositChanged;
use Functional\Deposit\Exceptions\DepositRefusedException;
use Functional\Deposit\History\DepositHistory;
use Functional\Deposit\Models\Deposit;
use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class CorrectDepositPayment
{
    public function __construct(private readonly DepositHistory $depositHistory) {}

    public function handle(Deposit $deposit, PaymentMethod $method, ?string $reference, string $reason, Model&AgencyMember $author): Deposit
    {
        $paymentReference = $reference === null || trim($reference) === '' ? null : trim($reference);
        $correctionReason = trim($reason);

        $correctedDeposit = DB::transaction(function () use ($deposit, $method, $paymentReference, $correctionReason, $author): Deposit {
            $lockedDeposit = Deposit::query()->with('reservation')->lockForUpdate()->findOrFail($deposit->id);

            match (true) {
                $lockedDeposit->status->isFinal() => throw DepositRefusedException::alreadyClosed(),
                $correctionReason === '' => throw DepositRefusedException::reasonRequired(),
                $method->requiresReference() && $paymentReference === null => throw DepositRefusedException::referenceRequired($method),
                default => null,
            };

            $previousMethod = $lockedDeposit->payment_method;
            $previousReference = $lockedDeposit->payment_reference;
            $lockedDeposit->update(['payment_method' => $method, 'payment_reference' => $paymentReference]);

            $this->depositHistory->record($lockedDeposit->reservation, DepositHistoryEvent::PaymentCorrected, $author, [
                'reason' => $correctionReason,
                'previous_method' => $previousMethod->label(),
                'previous_reference' => $previousReference,
                'method' => $method->label(),
                'reference' => $paymentReference,
            ]);

            return $lockedDeposit;
        });

        DepositChanged::dispatch($correctedDeposit->reservation_id);

        return $correctedDeposit;
    }
}
