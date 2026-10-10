<?php

namespace Functional\Deposit\States;

use Functional\Deposit\Enums\DepositStatus;

final class SettledDeposit implements DepositState
{
    use RefusesDepositTransitions;

    public function status(): DepositStatus
    {
        return DepositStatus::Settled;
    }
}
