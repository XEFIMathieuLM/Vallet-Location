<?php

namespace Functional\Booking\Extensions;

use Functional\Booking\Contracts\ReservationRequestGuard;

final class ReservationRequestGuards
{
    /**
     * @var list<class-string<ReservationRequestGuard>>
     */
    private array $guardClasses = [];

    /**
     * @param  class-string<ReservationRequestGuard>  $guardClass
     */
    public function register(string $guardClass): void
    {
        $this->guardClasses[] = $guardClass;
    }

    /**
     * @return list<ReservationRequestGuard>
     */
    public function all(): array
    {
        return array_map(fn (string $guardClass): ReservationRequestGuard => app($guardClass), $this->guardClasses);
    }
}
