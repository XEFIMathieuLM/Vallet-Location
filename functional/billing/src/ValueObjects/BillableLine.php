<?php

namespace Functional\Billing\ValueObjects;

use Functional\Billing\Enums\BillableLineType;

final readonly class BillableLine
{
    public function __construct(
        public string $idempotencyKey,
        public BillableLineType $type,
        public ?string $customerRef,
        public string $reservationRef,
        public string $machineReference,
        public string $machineCategory,
        public string $homeAgency,
        public string $bookingAgency,
        public ?string $periodStart = null,
        public ?string $periodEnd = null,
        public ?string $periodKind = null,
        public ?int $days = null,
        public ?string $damageView = null,
        public ?string $damageComment = null,
        public ?string $label = null,
        public ?int $amountExclTaxCents = null,
    ) {}

    /**
     * @return array<string, string|int|null>
     */
    public function toArray(): array
    {
        return [
            'idempotency_key' => $this->idempotencyKey,
            'type' => $this->type->value,
            'customer_ref' => $this->customerRef,
            'reservation_ref' => $this->reservationRef,
            'machine_reference' => $this->machineReference,
            'machine_category' => $this->machineCategory,
            'home_agency' => $this->homeAgency,
            'booking_agency' => $this->bookingAgency,
            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,
            'period_kind' => $this->periodKind,
            'days' => $this->days,
            'damage_view' => $this->damageView,
            'damage_comment' => $this->damageComment,
            'label' => $this->label,
            'amount_excl_tax_cents' => $this->amountExclTaxCents,
        ];
    }
}
