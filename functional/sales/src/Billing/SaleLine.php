<?php

namespace Functional\Sales\Billing;

use Functional\Billing\Enums\BillableLineType;
use Functional\Billing\Lines\BillableLine;
use Functional\Billing\Money\Money;

final readonly class SaleLine implements BillableLine
{
    public function __construct(
        private string $idempotencyKey,
        private ?string $customerRef,
        private string $saleRef,
        private string $machineReference,
        private string $machineCategory,
        private string $homeAgency,
        private string $sellingAgency,
        private string $saleDate,
        private string $label,
        private Money $price,
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
        return $this->price;
    }

    public function toArray(): array
    {
        return [
            'idempotency_key' => $this->idempotencyKey,
            'type' => BillableLineType::UsedMachineSale->value,
            'customer_ref' => $this->customerRef,
            'reservation_ref' => null,
            'machine_reference' => $this->machineReference,
            'machine_category' => $this->machineCategory,
            'home_agency' => $this->homeAgency,
            'booking_agency' => $this->sellingAgency,
            'period_start' => null,
            'period_end' => null,
            'period_kind' => null,
            'days' => null,
            'damage_view' => null,
            'damage_comment' => null,
            'label' => $this->label,
            'amount_excl_tax_cents' => $this->price->minorUnits,
            'source_ref' => $this->saleRef,
            'sale_date' => $this->saleDate,
            'purchase_order_number' => null,
        ];
    }
}
