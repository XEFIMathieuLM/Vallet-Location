<?php

namespace Functional\Booking\Planning;

use Functional\Booking\Models\Reservation;

final readonly class PlanningCell
{
    public function __construct(
        public PlanningCellKind $kind,
        public ?Reservation $reservation = null,
    ) {}

    public function label(): string
    {
        if ($this->reservation !== null) {
            return "{$this->reservation->customer->name} · {$this->reservation->status->label()}";
        }

        return $this->kind->label();
    }
}
