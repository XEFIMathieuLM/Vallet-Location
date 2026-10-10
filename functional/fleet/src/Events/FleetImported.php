<?php

namespace Functional\Fleet\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class FleetImported
{
    use Dispatchable;

    public function __construct(public readonly int $createdCount) {}
}
