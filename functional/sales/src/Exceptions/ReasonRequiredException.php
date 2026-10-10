<?php

namespace Functional\Sales\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;

final class ReasonRequiredException extends RefusalException
{
    public static function missing(): self
    {
        return new self('A reason is required for this sale action.', 'sales::refusals.reason_required');
    }
}
