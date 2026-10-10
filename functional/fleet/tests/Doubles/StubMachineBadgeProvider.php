<?php

namespace Functional\Fleet\Tests\Doubles;

use Functional\Fleet\Contracts\MachineBadgeProvider;
use Functional\Fleet\Data\MachineBadge;

final class StubMachineBadgeProvider implements MachineBadgeProvider
{
    public const LISTENER = 'echo-private:stub,.stub.changed';

    /**
     * @var list<list<int>>
     */
    public array $requestedMachineIds = [];

    public function badgesFor(array $machineIds): array
    {
        $this->requestedMachineIds[] = $machineIds;

        return array_fill_keys($machineIds, [new MachineBadge('Badge de test', 'blue', 'https://example.test/badge')]);
    }

    public function refreshListeners(): array
    {
        return [self::LISTENER];
    }
}
