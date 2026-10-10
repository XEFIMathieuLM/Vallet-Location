<?php

namespace Functional\Billing\Enums;

enum BillablePeriodKind: string
{
    case Intermediate = 'intermediate';
    case Final = 'final';

    public function label(): string
    {
        return __("billing::enums.billable_period_kind.{$this->value}");
    }
}
