<?php

namespace Functional\Billing\Lines;

use Functional\Billing\Contracts\PurchaseOrderNumbers;

final class NullPurchaseOrderNumbers implements PurchaseOrderNumbers
{
    public function forReservation(int $reservationId): ?string
    {
        return null;
    }

    public function forReservations(array $reservationIds): array
    {
        return [];
    }
}
