<?php

namespace Functional\Billing\Exceptions;

use RuntimeException;

final class MissingGoLiveDateException extends RuntimeException
{
    public static function make(): self
    {
        return new self('The billing go-live date (BILLING_GO_LIVE_DATE) is missing or invalid: nothing is transmitted.');
    }
}
