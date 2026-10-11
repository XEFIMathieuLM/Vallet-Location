<?php

namespace App\Dashboard;

use Carbon\CarbonImmutable;
use Functional\Booking\Models\Reservation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final readonly class UpcomingDays
{
    /**
     * @param  list<UpcomingDay>  $days
     */
    public function __construct(public array $days) {}

    /**
     * @param  Collection<int, Model>  $upcomingReservations
     */
    public static function after(CarbonImmutable $today, int $numberOfDays, Collection $upcomingReservations): self
    {
        $reservationsByDate = $upcomingReservations
            ->whereInstanceOf(Reservation::class)
            ->groupBy(fn (Reservation $reservation): string => $reservation->start_date->toDateString());

        return new self(array_map(
            fn (int $dayOffset): UpcomingDay => new UpcomingDay(
                $today->addDays($dayOffset),
                collect($reservationsByDate->get($today->addDays($dayOffset)->toDateString(), [])),
            ),
            $numberOfDays > 0 ? range(1, $numberOfDays) : [],
        ));
    }
}
