<?php

namespace Functional\Sales\Enums;

use Functional\Fleet\Contracts\HasLabel;

enum OfferTransition: string implements HasLabel
{
    case Accept = 'accept';
    case Reject = 'reject';
    case Withdraw = 'withdraw';

    public function label(): string
    {
        return __("sales::sales.offer_transitions.{$this->value}");
    }
}
