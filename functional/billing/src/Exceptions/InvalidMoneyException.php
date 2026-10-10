<?php

namespace Functional\Billing\Exceptions;

use DomainException;

final class InvalidMoneyException extends DomainException
{
    public static function malformed(string $typedAmount): self
    {
        return new self("Amount '{$typedAmount}' is not a positive amount with at most two decimals.");
    }

    public static function notMoney(string $attribute): self
    {
        return new self("Attribute '{$attribute}' only stores a Money value.");
    }
}
