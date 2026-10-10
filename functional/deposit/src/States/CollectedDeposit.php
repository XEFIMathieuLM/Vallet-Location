<?php

namespace Functional\Deposit\States;

use Functional\Deposit\Enums\DepositStatus;

final class CollectedDeposit implements DepositState
{
    use RefusesDepositTransitions;

    public function status(): DepositStatus
    {
        return DepositStatus::Collected;
    }

    public function toRefund(): DepositState
    {
        return new DepositToRefund;
    }

    public function blockByDamage(): DepositState
    {
        return new DepositBlockedByDamage;
    }
}
