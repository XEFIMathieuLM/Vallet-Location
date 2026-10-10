<?php

namespace Functional\Booking\Enums;

use Functional\Fleet\Contracts\HasLabel;

enum CustomerType: string implements HasLabel
{
    case Individual = 'individual';
    case Professional = 'professional';

    public function label(): string
    {
        return __("booking::customers.types.{$this->value}");
    }
}
