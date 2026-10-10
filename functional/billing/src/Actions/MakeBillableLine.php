<?php

namespace Functional\Billing\Actions;

use Functional\Billing\Enums\BillableLineType;
use Functional\Billing\Models\BillablePeriod;
use Functional\Billing\Models\CustomerBillingAccount;
use Functional\Billing\Models\DamageSettlement;
use Functional\Billing\Models\Transmission;
use Functional\Billing\ValueObjects\BillableLine;
use Functional\Booking\Models\Reservation;

final class MakeBillableLine
{
    public function handle(Transmission $transmission): BillableLine
    {
        $transmission->loadMissing([
            'reservation.machine.category', 'reservation.machine.agency', 'reservation.agency',
            'billablePeriod',
        ]);

        return $transmission->billablePeriod instanceof BillablePeriod
            ? $this->forPeriod($transmission, $transmission->billablePeriod)
            : $this->forDamage($transmission, $transmission->damageSettlement()->with('damage.view')->firstOrFail());
    }

    private function forPeriod(Transmission $transmission, BillablePeriod $billablePeriod): BillableLine
    {
        return new BillableLine(
            ...$this->commonFields($transmission, BillableLineType::RentalPeriod),
            periodStart: $billablePeriod->start_date->toDateString(),
            periodEnd: $billablePeriod->end_date->toDateString(),
            periodKind: $billablePeriod->kind->value,
            days: $billablePeriod->days,
        );
    }

    private function forDamage(Transmission $transmission, DamageSettlement $damageSettlement): BillableLine
    {
        return new BillableLine(
            ...$this->commonFields($transmission, BillableLineType::Damage),
            damageView: $damageSettlement->damage->view->label,
            damageComment: $damageSettlement->damage->comment,
            label: $damageSettlement->label,
            amountExclTax: $damageSettlement->amount,
        );
    }

    /**
     * @return array{idempotencyKey: string, type: BillableLineType, customerRef: string|null, reservationRef: string, machineReference: string, machineCategory: string, homeAgency: string, bookingAgency: string}
     */
    private function commonFields(Transmission $transmission, BillableLineType $type): array
    {
        $reservation = $transmission->reservation;

        return [
            'idempotencyKey' => $transmission->uuid,
            'type' => $type,
            'customerRef' => $this->customerRef($reservation),
            'reservationRef' => (string) $reservation->id,
            'machineReference' => $reservation->machine->reference,
            'machineCategory' => $reservation->machine->category->name,
            'homeAgency' => $reservation->machine->agency->name,
            'bookingAgency' => $reservation->agency->name,
        ];
    }

    private function customerRef(Reservation $reservation): ?string
    {
        return CustomerBillingAccount::query()->where('customer_id', $reservation->customer_id)->value('external_ref');
    }
}
