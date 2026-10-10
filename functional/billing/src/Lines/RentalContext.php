<?php

namespace Functional\Billing\Lines;

use Functional\Billing\Models\Transmission;

final readonly class RentalContext
{
    private function __construct(
        public string $idempotencyKey,
        public ?string $customerRef,
        public string $reservationRef,
        public string $machineReference,
        public string $machineCategory,
        public string $homeAgency,
        public string $bookingAgency,
        public ?string $purchaseOrderNumber,
    ) {}

    public static function fromTransmission(Transmission $transmission, ?string $customerRef, ?string $purchaseOrderNumber = null): self
    {
        $reservation = $transmission->reservation;

        return new self(
            idempotencyKey: $transmission->uuid,
            customerRef: $customerRef,
            reservationRef: (string) $reservation->id,
            machineReference: $reservation->machine->reference,
            machineCategory: $reservation->machine->category->name,
            homeAgency: $reservation->machine->agency->name,
            bookingAgency: $reservation->agency->name,
            purchaseOrderNumber: $purchaseOrderNumber,
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'idempotency_key' => $this->idempotencyKey,
            'customer_ref' => $this->customerRef,
            'reservation_ref' => $this->reservationRef,
            'machine_reference' => $this->machineReference,
            'machine_category' => $this->machineCategory,
            'home_agency' => $this->homeAgency,
            'booking_agency' => $this->bookingAgency,
            'purchase_order_number' => $this->purchaseOrderNumber,
        ];
    }
}
