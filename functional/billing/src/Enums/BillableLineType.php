<?php

namespace Functional\Billing\Enums;

enum BillableLineType: string
{
    case RentalPeriod = 'rental_period';
    case Damage = 'damage';
}
