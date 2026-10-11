<?php

namespace App\Dashboard;

use Carbon\CarbonImmutable;
use Functional\Booking\Models\Reservation;
use Illuminate\Support\Collection;

final readonly class UpcomingDay
{
    /**
     * @param  Collection<int, Reservation>  $reservations
     */
    public function __construct(
        public CarbonImmutable $date,
        public Collection $reservations,
    ) {}
}
