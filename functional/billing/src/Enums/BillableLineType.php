<?php

namespace Functional\Billing\Enums;

use Functional\Fleet\Contracts\HasLabel;

enum BillableLineType: string implements HasLabel
{
    case RentalPeriod = 'rental_period';
    case Damage = 'damage';
    case UsedMachineSale = 'used_machine_sale';

    public function label(): string
    {
        return __("billing::transmissions.line_types.{$this->value}");
    }
}
