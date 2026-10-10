<?php

namespace Functional\Billing\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;

final class InvalidDamageSettlementException extends RefusalException
{
    public static function because(string $reason): self
    {
        return new self(__("billing::damages.refusals.{$reason}"));
    }
}
