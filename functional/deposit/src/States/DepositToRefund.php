<?php

namespace Functional\Deposit\States;

use Functional\Deposit\Enums\DepositStatus;

final class DepositToRefund implements DepositState
{
    use RefusesDepositTransitions;

    public function status(): DepositStatus
    {
        return DepositStatus::ToRefund;
    }

    public function blockByDamage(): DepositState
    {
        return new DepositBlockedByDamage;
    }

    public function refund(): DepositState
    {
        return new RefundedDeposit;
    }
}
