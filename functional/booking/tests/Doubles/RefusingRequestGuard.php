<?php

namespace Functional\Booking\Tests\Doubles;

use Carbon\CarbonImmutable;
use Functional\Booking\Contracts\ReservationRequestGuard;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\Eloquent\Builder;

final class RefusingRequestGuard implements ReservationRequestGuard
{
    public function ensureCanReserve(Machine $lockedMachine, CarbonImmutable $startDate, CarbonImmutable $endDate): void
    {
        throw GuardRefusalException::missingPhotos('Machine bloquée par une autre fonctionnalité.');
    }

    public function excludeUnavailable(Builder $machines, CarbonImmutable $startDate, CarbonImmutable $endDate): void
    {
        $machines->whereRaw('false');
    }
}
