<?php

namespace Functional\Deposit\States;

use Functional\Deposit\Enums\DepositStatus;

final class DepositToSettle implements DepositState
{
    use RefusesDepositTransitions;

    public function status(): DepositStatus
    {
        return DepositStatus::ToSettle;
    }

    public function blockByDamage(): DepositState
    {
        return new DepositBlockedByDamage;
    }

    public function settle(): DepositState
    {
        return new SettledDeposit;
    }
}
