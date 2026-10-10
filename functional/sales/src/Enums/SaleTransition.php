<?php

namespace Functional\Sales\Enums;

use Functional\Fleet\Contracts\HasLabel;

enum SaleTransition: string implements HasLabel
{
    case Reserve = 'reserve';
    case Release = 'release';
    case Sell = 'sell';
    case Cancel = 'cancel';

    public function label(): string
    {
        return __("sales::sales.transitions.{$this->value}");
    }
}
