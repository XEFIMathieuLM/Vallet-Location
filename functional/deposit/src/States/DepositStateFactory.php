<?php

namespace Functional\Deposit\States;

use Functional\Deposit\Enums\DepositStatus;

final class DepositStateFactory
{
    public static function fromStatus(DepositStatus $status): DepositState
    {
        return match ($status) {
            DepositStatus::Collected => new CollectedDeposit,
            DepositStatus::ToRefund => new DepositToRefund,
            DepositStatus::BlockedByDamage => new DepositBlockedByDamage,
            DepositStatus::ToSettle => new DepositToSettle,
            DepositStatus::Refunded => new RefundedDeposit,
            DepositStatus::Settled => new SettledDeposit,
        };
    }
}
