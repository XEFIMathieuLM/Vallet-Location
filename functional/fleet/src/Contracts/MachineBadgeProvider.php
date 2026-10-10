<?php

namespace Functional\Fleet\Contracts;

use Functional\Fleet\Data\MachineBadge;

interface MachineBadgeProvider
{
    /**
     * @param  list<int>  $machineIds
     * @return array<int, list<MachineBadge>>
     */
    public function badgesFor(array $machineIds): array;

    /**
     * @return list<string>
     */
    public function refreshListeners(): array;
}
