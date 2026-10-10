<?php

namespace Functional\Booking\Planning;

use Functional\Fleet\Models\Machine;

final readonly class PlanningRow
{
    /**
     * @param  array<string, PlanningCell>  $cells  keyed by Y-m-d date
     */
    public function __construct(
        public Machine $machine,
        public array $cells,
    ) {}
}
