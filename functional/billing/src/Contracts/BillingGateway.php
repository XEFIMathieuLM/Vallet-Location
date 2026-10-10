<?php

namespace Functional\Billing\Contracts;

use Functional\Billing\Exceptions\BillingSoftwareRejectedException;
use Functional\Billing\Exceptions\BillingSoftwareUnreachableException;
use Functional\Billing\ValueObjects\BillableLine;

interface BillingGateway
{
    /**
     * Sends the line once per idempotency key and returns the reference given by the billing software.
     *
     * @throws BillingSoftwareRejectedException
     * @throws BillingSoftwareUnreachableException
     */
    public function send(BillableLine $line): string;
}
