<?php

namespace Functional\Deposit\Actions;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Deposit\Enums\DepositHistoryEvent;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Enums\PaymentMethod;
use Functional\Deposit\Events\DepositChanged;
use Functional\Deposit\Exceptions\DepositRefusedException;
use Functional\Deposit\History\DepositHistory;
use Functional\Deposit\Models\Deposit;
use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class CollectDeposit
{
    public function __construct(
        private readonly ResolveDepositAmount $resolveDepositAmount,
        private readonly DepositHistory $depositHistory,
    ) {}

    public function handle(Reservation $reservation, PaymentMethod $method, ?string $reference, Model&AgencyMember $author): Deposit
    {
        $paymentReference = $reference === null || trim($reference) === '' ? null : trim($reference);

        $deposit = DB::transaction(function () use ($reservation, $method, $paymentReference, $author): Deposit {
            $lockedReservation = Reservation::query()->with('customer')->lockForUpdate()->findOrFail($reservation->id);
            $this->ensureCollectable($lockedReservation, $method, $paymentReference);

            $deposit = Deposit::query()->create([
                'reservation_id' => $lockedReservation->id,
                'amount' => $this->resolveDepositAmount->forReservation($lockedReservation),
                'payment_method' => $method,
                'payment_reference' => $paymentReference,
                'status' => DepositStatus::Collected,
                'collected_by' => $author->getKey(),
                'collected_agency_id' => $author->agencyId(),
                'collected_at' => CarbonImmutable::now(),
            ]);

            $this->depositHistory->record($lockedReservation, DepositHistoryEvent::Collected, $author, [
                'amount' => $deposit->amount->format(),
                'method' => $method->label(),
                'reference' => $paymentReference,
            ]);

            return $deposit;
        });

        DepositChanged::dispatch($reservation->id);

        return $deposit;
    }

    private function ensureCollectable(Reservation $reservation, PaymentMethod $method, ?string $paymentReference): void
    {
        if ($reservation->status !== ReservationStatus::Confirmed) {
            throw DepositRefusedException::reservationNotConfirmed();
        }

        if ($reservation->customer->type !== CustomerType::Individual) {
            throw DepositRefusedException::customerNotIndividual();
        }

        if (Deposit::query()->whereBelongsTo($reservation)->exists()) {
            throw DepositRefusedException::alreadyCollected();
        }

        if ($method->requiresReference() && $paymentReference === null) {
            throw DepositRefusedException::referenceRequired($method);
        }
    }
}
