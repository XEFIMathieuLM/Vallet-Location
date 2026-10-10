<?php

namespace Functional\Deposit\Enums;

use Functional\Fleet\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case Cheque = 'cheque';
    case CardImprint = 'card_imprint';
    case Cash = 'cash';

    public function requiresReference(): bool
    {
        return $this !== self::Cash;
    }

    public function label(): string
    {
        return __("deposit::enums.payment_methods.{$this->value}");
    }
}
