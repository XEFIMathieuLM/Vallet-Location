<?php

namespace Functional\Billing\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;

final class DamageAlreadySettledException extends RefusalException
{
    public static function for(int $damageId): self
    {
        return new self("Damage #{$damageId} is already settled.", 'billing::damages.refusals.already_settled');
    }
}
