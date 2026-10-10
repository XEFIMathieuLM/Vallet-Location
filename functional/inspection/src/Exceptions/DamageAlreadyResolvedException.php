<?php

namespace Functional\Inspection\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Inspection\Models\Damage;

final class DamageAlreadyResolvedException extends RefusalException
{
    public static function for(Damage $damage): self
    {
        return new self(
            "Damage {$damage->id} is already resolved.",
            'inspection::damages.refusals.already_resolved',
        );
    }
}
