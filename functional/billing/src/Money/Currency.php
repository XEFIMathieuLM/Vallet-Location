<?php

namespace Functional\Billing\Money;

enum Currency: string
{
    case Eur = 'EUR';
    case Chf = 'CHF';

    public function minorUnitsPerUnit(): int
    {
        return 100;
    }
}
