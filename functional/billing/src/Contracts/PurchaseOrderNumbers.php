<?php

namespace Functional\Billing\Contracts;

interface PurchaseOrderNumbers
{
    public function forReservation(int $reservationId): ?string;

    /**
     * @param  list<int>  $reservationIds
     * @return array<int, string>
     */
    public function forReservations(array $reservationIds): array;
}
