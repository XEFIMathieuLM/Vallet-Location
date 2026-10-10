<?php

namespace Functional\Billing\Enums;

enum DamageOutcome: string
{
    case Billed = 'billed';
    case Waived = 'waived';

    public function label(): string
    {
        return __("billing::enums.damage_outcome.{$this->value}");
    }
}
