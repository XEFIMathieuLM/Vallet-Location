<?php

namespace Functional\Billing\Exceptions;

use RuntimeException;

final class UnknownBillingGatewayException extends RuntimeException
{
    public static function named(string $gatewayName): self
    {
        return new self("Unknown billing gateway [{$gatewayName}].");
    }
}
