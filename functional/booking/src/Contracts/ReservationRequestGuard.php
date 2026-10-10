<?php

namespace Functional\Booking\Contracts;

use Carbon\CarbonImmutable;
use Functional\Fleet\Exceptions\RefusalException;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\Eloquent\Builder;

interface ReservationRequestGuard
{
    /**
     * @throws RefusalException
     */
    public function ensureCanReserve(Machine $lockedMachine, CarbonImmutable $startDate, CarbonImmutable $endDate): void;

    /**
     * @param  Builder<Machine>  $machines
     */
    public function excludeUnavailable(Builder $machines, CarbonImmutable $startDate, CarbonImmutable $endDate): void;
}
