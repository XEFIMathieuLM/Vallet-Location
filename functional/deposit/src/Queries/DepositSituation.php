<?php

namespace Functional\Deposit\Queries;

use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Deposit\Actions\ResolveDepositAmount;
use Functional\Deposit\Enums\DepositSituationKind;
use Functional\Deposit\Models\Deposit;
use Functional\Deposit\ValueObjects\DepositSituationResult;

final class DepositSituation
{
    public function __construct(private readonly ResolveDepositAmount $resolveDepositAmount) {}

    public function for(Reservation $reservation): DepositSituationResult
    {
        $deposit = Deposit::query()->whereBelongsTo($reservation)->first();

        if ($deposit !== null) {
            return new DepositSituationResult(DepositSituationKind::Tracked, deposit: $deposit);
        }

        $customerType = $reservation->customer->type;

        return match (true) {
            $customerType === CustomerType::Professional => new DepositSituationResult(DepositSituationKind::NotRequired),
            $reservation->status !== ReservationStatus::Confirmed => new DepositSituationResult(DepositSituationKind::NotTracked),
            $customerType === null => new DepositSituationResult(DepositSituationKind::CustomerTypeMissing),
            default => new DepositSituationResult(DepositSituationKind::ToCollect, expectedAmount: $this->resolveDepositAmount->forReservation($reservation)),
        };
    }
}
