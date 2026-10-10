<?php

namespace Functional\Billing\Contracts;

use Functional\Billing\Exceptions\BillingSoftwareRejectedException;
use Functional\Billing\Exceptions\BillingSoftwareUnreachableException;
use Functional\Billing\Lines\BillableLine;

interface BillingGateway
{
    /**
     * Sends the line once per idempotency key and returns the reference given by the billing software.
     * The call is made outside any database transaction and must give up after `billing.gateway_timeout_seconds`.
     *
     * @throws BillingSoftwareRejectedException
     * @throws BillingSoftwareUnreachableException
     */
    public function send(BillableLine $line): string;
}
