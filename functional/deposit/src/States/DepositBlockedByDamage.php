<?php

namespace Functional\Deposit\States;

use Functional\Deposit\Enums\DepositStatus;

final class DepositBlockedByDamage implements DepositState
{
    use RefusesDepositTransitions;

    public function status(): DepositStatus
    {
        return DepositStatus::BlockedByDamage;
    }

    public function toSettle(): DepositState
    {
        return new DepositToSettle;
    }

    public function toRefund(): DepositState
    {
        return new DepositToRefund;
    }
}
