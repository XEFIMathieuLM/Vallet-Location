<?php

namespace Functional\Deposit\ValueObjects;

use Functional\Billing\Money\Money;

final readonly class DepositRetention
{
    private function __construct(
        public Money $retained,
        public Money $refunded,
    ) {}

    public static function fromBilledTotal(Money $deposit, Money $billedTotal): self
    {
        $retainedMinorUnits = min($deposit->minorUnits, max(0, $billedTotal->minorUnits));

        return new self(
            Money::fromStored($retainedMinorUnits, $deposit->currency),
            Money::fromStored($deposit->minorUnits - $retainedMinorUnits, $deposit->currency),
        );
    }
}
