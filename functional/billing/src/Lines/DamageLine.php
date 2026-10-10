<?php

namespace Functional\Billing\Lines;

use Functional\Billing\Enums\BillableLineType;
use Functional\Billing\Exceptions\InvalidMoneyException;
use Functional\Billing\Models\DamageSettlement;
use Functional\Billing\Money\Money;

final readonly class DamageLine implements BillableLine
{
    private function __construct(
        public RentalContext $rentalContext,
        public string $damageView,
        public string $damageComment,
        public string $label,
        public Money $amountExclTax,
    ) {}

    public static function fromSettlement(RentalContext $rentalContext, DamageSettlement $damageSettlement): self
    {
        return new self(
            rentalContext: $rentalContext,
            damageView: $damageSettlement->damage->view->label,
            damageComment: $damageSettlement->damage->comment,
            label: (string) $damageSettlement->label,
            amountExclTax: $damageSettlement->amount ?? throw InvalidMoneyException::missingForSettlement($damageSettlement->id),
        );
    }

    public function idempotencyKey(): string
    {
        return $this->rentalContext->idempotencyKey;
    }

    public function customerRef(): ?string
    {
        return $this->rentalContext->customerRef;
    }

    public function amountExclTax(): Money
    {
        return $this->amountExclTax;
    }

    public function toArray(): array
    {
        $rentalContext = $this->rentalContext->toArray();

        return [
            'idempotency_key' => $rentalContext['idempotency_key'],
            'type' => BillableLineType::Damage->value,
            ...array_diff_key($rentalContext, ['idempotency_key' => true]),
            'period_start' => null,
            'period_end' => null,
            'period_kind' => null,
            'days' => null,
            'damage_view' => $this->damageView,
            'damage_comment' => $this->damageComment,
            'label' => $this->label,
            'amount_excl_tax_cents' => $this->amountExclTax->minorUnits,
        ];
    }
}
