<?php

namespace Functional\Billing\Exceptions;

use Carbon\CarbonImmutable;
use DomainException;

final class InvalidDateRangeException extends DomainException
{
    public static function endsBeforeStart(CarbonImmutable $start, CarbonImmutable $end): self
    {
        return new self("Date range ends on {$end->toDateString()} before it starts on {$start->toDateString()}.");
    }
}
