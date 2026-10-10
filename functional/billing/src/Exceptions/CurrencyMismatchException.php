<?php

namespace Functional\Billing\Exceptions;

use DomainException;
use Functional\Billing\Money\Currency;

final class CurrencyMismatchException extends DomainException
{
    public static function between(Currency $expected, Currency $given): self
    {
        return new self("Cannot combine {$expected->value} and {$given->value} amounts.");
    }
}
