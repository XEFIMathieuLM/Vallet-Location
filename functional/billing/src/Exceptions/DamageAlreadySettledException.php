<?php

namespace Functional\Billing\Exceptions;

final class DamageAlreadySettledException extends BillingRefusalException
{
    public static function for(int $damageId): self
    {
        return new self("Damage #{$damageId} is already settled.", 'billing::damages.refusals.already_settled');
    }
}
