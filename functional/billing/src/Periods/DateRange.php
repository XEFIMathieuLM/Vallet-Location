<?php

namespace Functional\Billing\Periods;

use Carbon\CarbonImmutable;
use Functional\Billing\Exceptions\InvalidDateRangeException;

final readonly class DateRange
{
    public function __construct(public CarbonImmutable $start, public CarbonImmutable $end)
    {
        if ($end->lt($start)) {
            throw InvalidDateRangeException::endsBeforeStart($start, $end);
        }
    }

    public function days(): int
    {
        return (int) $this->start->startOfDay()->diffInDays($this->end->startOfDay()) + 1;
    }
}
