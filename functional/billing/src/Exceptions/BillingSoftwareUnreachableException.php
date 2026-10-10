<?php

namespace Functional\Billing\Exceptions;

use RuntimeException;

final class BillingSoftwareUnreachableException extends RuntimeException
{
    public static function make(): self
    {
        return new self(__('billing::transmissions.unreachable'));
    }
}
