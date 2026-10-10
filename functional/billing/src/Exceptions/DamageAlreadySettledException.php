<?php

namespace Functional\Billing\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;

final class DamageAlreadySettledException extends RefusalException
{
    public static function make(): self
    {
        return new self(__('billing::damages.refusals.already_settled'));
    }
}
