<?php

namespace Functional\Billing\Exceptions;

use DomainException;
use Functional\Billing\Enums\BillableLineType;

final class UnknownBillableSourceException extends DomainException
{
    public static function forType(BillableLineType $type): self
    {
        return new self("No billable source is registered for the line type [{$type->value}].");
    }
}
