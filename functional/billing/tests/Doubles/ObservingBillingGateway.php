<?php

namespace Functional\Billing\Tests\Doubles;

use Closure;
use Functional\Billing\Contracts\BillingGateway;
use Functional\Billing\Lines\BillableLine;

final class ObservingBillingGateway implements BillingGateway
{
    /**
     * @param  Closure(BillableLine): string  $onSend
     */
    public function __construct(private readonly Closure $onSend) {}

    public function send(BillableLine $line): string
    {
        return ($this->onSend)($line);
    }
}
