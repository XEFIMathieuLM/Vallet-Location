<?php

namespace Functional\Accounts\Billing;

use Functional\Accounts\Models\ReservationPurchaseOrder;
use Functional\Billing\Contracts\PurchaseOrderNumbers;

final class KeyAccountPurchaseOrderNumbers implements PurchaseOrderNumbers
{
    public function forReservation(int $reservationId): ?string
    {
        return ReservationPurchaseOrder::query()->where('reservation_id', $reservationId)->value('number');
    }

    public function forReservations(array $reservationIds): array
    {
        if ($reservationIds === []) {
            return [];
        }

        return ReservationPurchaseOrder::query()->whereIn('reservation_id', $reservationIds)->pluck('number', 'reservation_id')->all();
    }
}
