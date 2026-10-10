<?php

namespace Functional\Deposit\Enums;

use Functional\Fleet\Contracts\HasLabel;

enum DepositSituationKind: string implements HasLabel
{
    case NotRequired = 'not_required';
    case NotTracked = 'not_tracked';
    case CustomerTypeMissing = 'customer_type_missing';
    case ToCollect = 'to_collect';
    case Tracked = 'tracked';

    public function label(): string
    {
        return __("deposit::enums.situations.{$this->value}");
    }
}
