<?php

namespace Functional\Fleet\Events;

use Functional\Fleet\Models\Machine;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MachineChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Machine $machine) {}
}
