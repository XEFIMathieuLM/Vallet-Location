<?php

namespace Functional\Fleet\Livewire\Concerns;

use Functional\Fleet\Data\MachineBadge;
use Functional\Fleet\Extensions\MachineBadges;
use Functional\Fleet\Models\Machine;

trait DisplaysMachineBadges
{
    /**
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return array_fill_keys(app(MachineBadges::class)->refreshListeners(), '$refresh');
    }

    /**
     * @param  iterable<Machine>  $machines
     * @return array<int, list<MachineBadge>>
     */
    protected function badgesFor(iterable $machines): array
    {
        $machineIds = [];

        foreach ($machines as $machine) {
            $machineIds[] = $machine->id;
        }

        return app(MachineBadges::class)->forMachines($machineIds);
    }
}
