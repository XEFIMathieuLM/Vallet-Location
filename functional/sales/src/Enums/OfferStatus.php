<?php

namespace Functional\Sales\Enums;

use Functional\Fleet\Contracts\HasLabel;

enum OfferStatus: string implements HasLabel
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return __("sales::sales.offer_status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'blue',
            self::Accepted => 'green',
            self::Rejected => 'red',
            self::Withdrawn => 'zinc',
        };
    }
}
