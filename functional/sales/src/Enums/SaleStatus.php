<?php

namespace Functional\Sales\Enums;

use Functional\Fleet\Contracts\HasLabel;

enum SaleStatus: string implements HasLabel
{
    case Listed = 'listed';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("sales::sales.status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Listed => 'blue',
            self::Reserved => 'amber',
            self::Sold => 'green',
            self::Cancelled => 'zinc',
        };
    }
}
