<?php

namespace Functional\Billing\Lines;

use Functional\Billing\Enums\BillableLineType;
use Functional\Billing\Enums\BillablePeriodKind;
use Functional\Billing\Models\BillablePeriod;
use Functional\Billing\Money\Money;
use Functional\Billing\Periods\DateRange;

final readonly class RentalPeriodLine implements BillableLine
{
    private function __construct(
        public RentalContext $rentalContext,
        public DateRange $period,
        public BillablePeriodKind $kind,
    ) {}

    public static function fromPeriod(RentalContext $rentalContext, BillablePeriod $billablePeriod): self
    {
        return new self($rentalContext, new DateRange($billablePeriod->start_date, $billablePeriod->end_date), $billablePeriod->kind);
    }

    public function idempotencyKey(): string
    {
        return $this->rentalContext->idempotencyKey;
    }

    public function customerRef(): ?string
    {
        return $this->rentalContext->customerRef;
    }

    public function amountExclTax(): ?Money
    {
        return null;
    }

    public function toArray(): array
    {
        $rentalContext = $this->rentalContext->toArray();

        return [
            'idempotency_key' => $rentalContext['idempotency_key'],
            'type' => BillableLineType::RentalPeriod->value,
            ...array_diff_key($rentalContext, ['idempotency_key' => true]),
            'period_start' => $this->period->start->toDateString(),
            'period_end' => $this->period->end->toDateString(),
            'period_kind' => $this->kind->value,
            'days' => $this->period->days(),
            'damage_view' => null,
            'damage_comment' => null,
            'label' => null,
            'amount_excl_tax_cents' => null,
        ];
    }
}
