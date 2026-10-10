<?php

namespace Functional\Deposit\Guards;

use Functional\Booking\Contracts\ReservationTransitionGuard;
use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Models\Reservation;
use Functional\Deposit\Actions\ResolveDepositAmount;
use Functional\Deposit\Exceptions\DepositRefusedException;
use Functional\Deposit\Models\Deposit;

final class DepositCollectedGuard implements ReservationTransitionGuard
{
    public function __construct(private readonly ResolveDepositAmount $resolveDepositAmount) {}

    public function beforeDeparture(Reservation $reservation): void
    {
        $customerType = $reservation->customer()->value('type');

        if ($customerType === null) {
            throw DepositRefusedException::customerTypeMissing();
        }

        if ($customerType === CustomerType::Individual && ! Deposit::query()->whereBelongsTo($reservation)->exists()) {
            throw DepositRefusedException::notCollected($this->resolveDepositAmount->forReservation($reservation));
        }
    }

    public function beforeReturn(Reservation $reservation): void {}
}
