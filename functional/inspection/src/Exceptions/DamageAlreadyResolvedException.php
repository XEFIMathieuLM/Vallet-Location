<?php

namespace Functional\Inspection\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;

final class DamageAlreadyResolvedException extends RefusalException
{
    public static function make(): self
    {
        return new self(__('inspection::damages.refusals.already_resolved'));
    }
}
