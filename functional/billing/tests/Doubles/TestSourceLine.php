<?php

namespace Functional\Billing\Tests\Doubles;

use Functional\Billing\Enums\BillableLineType;
use Functional\Billing\Lines\BillableLine;
use Functional\Billing\Money\Money;

final readonly class TestSourceLine implements BillableLine
{
    public function __construct(
        private string $idempotencyKey,
        private ?string $customerRef,
        private string $sourceRef,
        private Money $amount,
    ) {}

    public function idempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function customerRef(): ?string
    {
        return $this->customerRef;
    }

    public function amountExclTax(): Money
    {
        return $this->amount;
    }

    public function toArray(): array
    {
        return [
            'idempotency_key' => $this->idempotencyKey,
            'type' => BillableLineType::UsedMachineSale->value,
            'customer_ref' => $this->customerRef,
            'source_ref' => $this->sourceRef,
            'sale_date' => '2026-11-15',
            'label' => 'Ligne de test',
            'amount_excl_tax_cents' => $this->amount->minorUnits,
        ];
    }
}
