<?php

namespace Functional\Deposit\Enums;

use Functional\Fleet\Contracts\HasLabel;

enum DepositStatus: string implements HasLabel
{
    case Collected = 'collected';
    case ToRefund = 'to_refund';
    case BlockedByDamage = 'blocked_by_damage';
    case ToSettle = 'to_settle';
    case Refunded = 'refunded';
    case Settled = 'settled';

    public function isFinal(): bool
    {
        return in_array($this, [self::Refunded, self::Settled], true);
    }

    public function isAwaitingAction(): bool
    {
        return in_array($this, [self::ToRefund, self::BlockedByDamage, self::ToSettle], true);
    }

    public function label(): string
    {
        return __("deposit::enums.statuses.{$this->value}");
    }
}
