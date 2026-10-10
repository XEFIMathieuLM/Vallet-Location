<?php

namespace Functional\Booking\Events;

use Functional\Booking\Models\Customer;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CustomerChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    /**
     * @param  list<string>  $changedAttributes
     */
    public function __construct(
        public readonly Customer $customer,
        public readonly array $changedAttributes,
    ) {}
}
