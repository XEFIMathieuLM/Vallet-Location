<?php

namespace Functional\Billing\Exceptions;

use RuntimeException;

final class BillingSoftwareRejectedException extends RuntimeException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
