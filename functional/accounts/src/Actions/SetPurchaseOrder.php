<?php

namespace Functional\Accounts\Actions;

use Carbon\CarbonImmutable;
use Functional\Accounts\Enums\AccountsHistoryEvent;
use Functional\Accounts\Exceptions\PurchaseOrderFrozenException;
use Functional\Accounts\Exceptions\PurchaseOrderRefusedException;
use Functional\Accounts\Models\ReservationPurchaseOrder;
use Functional\Accounts\Support\AccountsHistory;
use Functional\Accounts\Support\PurchaseOrderNumber;
use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

final class SetPurchaseOrder
{
    public function __construct(private readonly AccountsHistory $accountsHistory) {}

    public function handle(Authenticatable&AgencyMember $author, Reservation $reservation, string $rawNumber): ReservationPurchaseOrder
    {
        return DB::transaction(function () use ($author, $reservation, $rawNumber): ReservationPurchaseOrder {
            $lockedReservation = Reservation::query()->with('customer')->lockForUpdate()->findOrFail($reservation->id);
            $this->ensureCanBeEntered($lockedReservation);
            $number = PurchaseOrderNumber::normalize($rawNumber);

            $purchaseOrder = ReservationPurchaseOrder::query()->firstOrNew(['reservation_id' => $lockedReservation->id]);
            $previousNumber = $purchaseOrder->exists ? $purchaseOrder->number : null;

            if ($previousNumber === $number) {
                return $purchaseOrder;
            }

            $purchaseOrder->fill([
                'number' => $number,
                'entered_by' => $author->getAuthIdentifier(),
                'agency_id' => $author->agencyId(),
                'entered_at' => CarbonImmutable::now(),
            ])->save();

            $this->recordHistory($lockedReservation, $author, $number, $previousNumber);

            return $purchaseOrder;
        });
    }

    private function ensureCanBeEntered(Reservation $reservation): void
    {
        if ($reservation->status !== ReservationStatus::Confirmed) {
            throw PurchaseOrderFrozenException::for($reservation);
        }

        if ($reservation->customer->type !== CustomerType::Professional) {
            throw PurchaseOrderRefusedException::notProfessional($reservation);
        }
    }

    private function recordHistory(Reservation $reservation, Authenticatable&AgencyMember $author, string $number, ?string $previousNumber): void
    {
        if ($previousNumber === null) {
            $this->accountsHistory->record($reservation, AccountsHistoryEvent::PurchaseOrderEntered, $author, ['number' => $number]);

            return;
        }

        $this->accountsHistory->record($reservation, AccountsHistoryEvent::PurchaseOrderCorrected, $author, ['previous_number' => $previousNumber, 'number' => $number]);
    }
}
