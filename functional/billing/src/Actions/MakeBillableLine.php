<?php

namespace Functional\Billing\Actions;

use Functional\Billing\Lines\BillableLine;
use Functional\Billing\Lines\DamageLine;
use Functional\Billing\Lines\RentalContext;
use Functional\Billing\Lines\RentalPeriodLine;
use Functional\Billing\Models\BillablePeriod;
use Functional\Billing\Models\CustomerBillingAccount;
use Functional\Billing\Models\Transmission;
use Functional\Booking\Models\Reservation;

final class MakeBillableLine
{
    public function handle(Transmission $transmission): BillableLine
    {
        $transmission->loadMissing([
            'reservation.machine.category', 'reservation.machine.agency', 'reservation.agency',
            'billablePeriod',
        ]);
        $rentalContext = RentalContext::fromTransmission($transmission, $this->customerRef($transmission->reservation));

        return $transmission->billablePeriod instanceof BillablePeriod
            ? RentalPeriodLine::fromPeriod($rentalContext, $transmission->billablePeriod)
            : DamageLine::fromSettlement($rentalContext, $transmission->damageSettlement()->with('damage.view')->firstOrFail());
    }

    private function customerRef(Reservation $reservation): ?string
    {
        return CustomerBillingAccount::query()->where('customer_id', $reservation->customer_id)->value('external_ref');
    }
}
