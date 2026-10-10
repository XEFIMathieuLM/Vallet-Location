<?php

namespace Functional\Billing\Actions;

use Functional\Billing\Enums\BillableLineType;
use Functional\Billing\Exceptions\TransmissionWithoutSourceException;
use Functional\Billing\Extensions\BillableSources;
use Functional\Billing\Lines\BillableLine;
use Functional\Billing\Lines\DamageLine;
use Functional\Billing\Lines\RentalContext;
use Functional\Billing\Lines\RentalPeriodLine;
use Functional\Billing\Models\BillablePeriod;
use Functional\Billing\Models\Transmission;

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

    public function __construct(private readonly BillableSources $billableSources) {}

    public function handle(Transmission $transmission): BillableLine
    {
        if ($transmission->source_type instanceof BillableLineType) {
            return $this->billableSources->for($transmission->source_type)->line($transmission);
        }

        $transmission->loadMissing(self::RELATIONS);
        $rentalContext = RentalContext::fromTransmission($transmission, $transmission->customerBillingAccount?->external_ref);

        if ($transmission->billablePeriod instanceof BillablePeriod) {
            return RentalPeriodLine::fromPeriod($rentalContext, $transmission->billablePeriod);
        }

        return DamageLine::fromSettlement($rentalContext, $transmission->damageSettlement ?? throw TransmissionWithoutSourceException::for($transmission->id));
    }
}
