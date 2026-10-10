<?php

namespace Functional\Sales\Exceptions;

use Carbon\CarbonImmutable;
use Functional\Fleet\Exceptions\RefusalException;

final class InvalidHandoverDateException extends RefusalException
{
    public static function inThePast(CarbonImmutable $plannedHandoverDate): self
    {
        return new self(
            "The planned handover date {$plannedHandoverDate->toDateString()} is in the past.",
            'sales::refusals.handover_date_in_the_past',
            ['date' => $plannedHandoverDate->format('d/m/Y')],
        );
    }
}
