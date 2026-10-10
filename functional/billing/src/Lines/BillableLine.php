<?php

namespace Functional\Billing\Lines;

use Functional\Billing\Money\Money;

interface BillableLine
{
    public function idempotencyKey(): string;

    public function customerRef(): ?string;

    public function amountExclTax(): ?Money;

    /**
     * @return array<string, string|int|null>
     */
    public function toArray(): array;
}
