<?php

namespace Functional\Booking\Extensions;

use Functional\Booking\Contracts\ReservationTransitionGuard;

final class ReservationTransitionGuards
{
    /**
     * @var list<class-string<ReservationTransitionGuard>>
     */
    private array $guardClasses = [];

    /**
     * @param  class-string<ReservationTransitionGuard>  $guardClass
     */
    public function register(string $guardClass): void
    {
        $this->guardClasses[] = $guardClass;
    }

    /**
     * @return list<ReservationTransitionGuard>
     */
    public function all(): array
    {
        return array_map(fn (string $guardClass): ReservationTransitionGuard => app($guardClass), $this->guardClasses);
    }
}
