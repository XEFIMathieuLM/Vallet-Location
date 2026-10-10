<?php

namespace Functional\Billing\Exceptions;

use RuntimeException;

final class FakeBillingGatewayNotAllowedException extends RuntimeException
{
    public static function in(string $environment): self
    {
        return new self("The fake billing gateway cannot be used in the [{$environment}] environment: configure BILLING_GATEWAY with the customer's billing software.");
    }
}
