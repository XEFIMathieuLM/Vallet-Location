<?php

namespace Functional\Billing\Actions;

use Functional\Billing\Contracts\PurchaseOrderNumbers;
use Functional\Billing\Enums\BillableLineType;
use Functional\Billing\Exceptions\TransmissionWithoutSourceException;
use Functional\Billing\Extensions\BillableSources;
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

    public function __construct(
        private readonly BillableSources $billableSources,
        private readonly PurchaseOrderNumbers $purchaseOrderNumbers,
    ) {}

    public function handle(Transmission $transmission): BillableLine
    {
        if ($transmission->source_type instanceof BillableLineType) {
            return $this->billableSources->for($transmission->source_type)->line($transmission);
        }

        return $this->rentalLine($transmission, $this->purchaseOrderNumbers->forReservation((int) $transmission->reservation_id));
    }

    /**
     * @param  Collection<int, Transmission>  $transmissions
     * @return list<BillableLine>
     */
    public function handleAll(Collection $transmissions): array
    {
        $reservationIds = array_values(array_unique(array_filter($transmissions->pluck('reservation_id')->all(), fn (?int $reservationId): bool => $reservationId !== null)));
        $numbersByReservation = $this->purchaseOrderNumbers->forReservations($reservationIds);

        return array_values($transmissions->map(fn (Transmission $transmission): BillableLine => $transmission->source_type instanceof BillableLineType
            ? $this->billableSources->for($transmission->source_type)->line($transmission)
            : $this->rentalLine($transmission, $numbersByReservation[(int) $transmission->reservation_id] ?? null))->all());
    }

    private function rentalLine(Transmission $transmission, ?string $purchaseOrderNumber): BillableLine
    {
        $transmission->loadMissing(self::RELATIONS);
        $rentalContext = RentalContext::fromTransmission($transmission, $transmission->customerBillingAccount?->external_ref, $purchaseOrderNumber);

        if ($transmission->billablePeriod instanceof BillablePeriod) {
            return RentalPeriodLine::fromPeriod($rentalContext, $transmission->billablePeriod);
        }

        return DamageLine::fromSettlement($rentalContext, $transmission->damageSettlement ?? throw TransmissionWithoutSourceException::for($transmission->id));
    }
}
