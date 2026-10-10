<?php

namespace Functional\Deposit\States;

use Functional\Deposit\Enums\DepositStatus;

final class RefundedDeposit implements DepositState
{
    use RefusesDepositTransitions;

    public function status(): DepositStatus
    {
        return DepositStatus::Refunded;
    }
}
