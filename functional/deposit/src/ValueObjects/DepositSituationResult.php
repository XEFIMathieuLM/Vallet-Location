<?php

namespace Functional\Deposit\ValueObjects;

use Functional\Billing\Money\Money;
use Functional\Deposit\Enums\DepositSituationKind;
use Functional\Deposit\Models\Deposit;

final readonly class DepositSituationResult
{
    public function __construct(
        public DepositSituationKind $kind,
        public ?Deposit $deposit = null,
        public ?Money $expectedAmount = null,
    ) {}

    public function isDepartureReady(): bool
    {
        return in_array($this->kind, [DepositSituationKind::NotRequired, DepositSituationKind::NotTracked, DepositSituationKind::Tracked], true);
    }
}
