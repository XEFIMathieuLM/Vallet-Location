<?php

namespace Functional\Billing\Actions;

use Functional\Billing\Contracts\PurchaseOrderNumbers;
use Functional\Billing\Exceptions\TransmissionWithoutSourceException;
use Functional\Billing\Lines\BillableLine;
use Functional\Billing\Lines\DamageLine;
use Functional\Billing\Lines\RentalContext;
use Functional\Billing\Lines\RentalPeriodLine;
use Functional\Billing\Models\BillablePeriod;
use Functional\Billing\Models\Transmission;
use Illuminate\Database\Eloquent\Collection;

final class MakeBillableLine
{
    public const RELATIONS = [
        'reservation.machine.category',
        'reservation.machine.agency',
        'reservation.agency',
        'billablePeriod',
        'damageSettlement.damage.view',
        'customerBillingAccount',
    ];

    public function __construct(private readonly PurchaseOrderNumbers $purchaseOrderNumbers) {}

    public function handle(Transmission $transmission): BillableLine
    {
        return $this->lineFor($transmission, $this->purchaseOrderNumbers->forReservation($transmission->reservation_id));
    }

    /**
     * @param  Collection<int, Transmission>  $transmissions
     * @return list<BillableLine>
     */
    public function handleAll(Collection $transmissions): array
    {
        $numbersByReservation = $this->purchaseOrderNumbers->forReservations(array_values(array_unique($transmissions->pluck('reservation_id')->all())));

        return array_values($transmissions->map(
            fn (Transmission $transmission): BillableLine => $this->lineFor($transmission, $numbersByReservation[$transmission->reservation_id] ?? null),
        )->all());
    }

    private function lineFor(Transmission $transmission, ?string $purchaseOrderNumber): BillableLine
    {
        $transmission->loadMissing(self::RELATIONS);
        $rentalContext = RentalContext::fromTransmission($transmission, $transmission->customerBillingAccount?->external_ref, $purchaseOrderNumber);

        if ($transmission->billablePeriod instanceof BillablePeriod) {
            return RentalPeriodLine::fromPeriod($rentalContext, $transmission->billablePeriod);
        }

        return DamageLine::fromSettlement($rentalContext, $transmission->damageSettlement ?? throw TransmissionWithoutSourceException::for($transmission->id));
    }
}
