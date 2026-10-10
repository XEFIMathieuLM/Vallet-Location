<?php

namespace Functional\Billing\Lines;

interface BillableLine
{
    public function idempotencyKey(): string;

    public function customerRef(): ?string;

    /**
     * @return array<string, string|int|null>
     */
    public function toArray(): array;
}
