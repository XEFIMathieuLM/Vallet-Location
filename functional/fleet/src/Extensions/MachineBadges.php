<?php

namespace Functional\Fleet\Extensions;

use Functional\Fleet\Contracts\MachineBadgeProvider;
use Functional\Fleet\Data\MachineBadge;

final class MachineBadges
{
    /**
     * @var list<class-string<MachineBadgeProvider>>
     */
    private array $providerClasses = [];

    /**
     * @param  class-string<MachineBadgeProvider>  $providerClass
     */
    public function register(string $providerClass): void
    {
        $this->providerClasses[] = $providerClass;
    }

    /**
     * @param  list<int>  $machineIds
     * @return array<int, list<MachineBadge>>
     */
    public function forMachines(array $machineIds): array
    {
        if ($machineIds === []) {
            return [];
        }

        $badgesByMachine = [];

        foreach ($this->providers() as $provider) {
            foreach ($provider->badgesFor($machineIds) as $machineId => $machineBadges) {
                $badgesByMachine[$machineId] = [...$badgesByMachine[$machineId] ?? [], ...$machineBadges];
            }
        }

        return $badgesByMachine;
    }

    /**
     * @return list<string>
     */
    public function refreshListeners(): array
    {
        return array_values(array_unique(array_merge([], ...array_map(
            fn (MachineBadgeProvider $provider): array => $provider->refreshListeners(),
            $this->providers(),
        ))));
    }

    /**
     * @return list<MachineBadgeProvider>
     */
    private function providers(): array
    {
        return array_map(fn (string $providerClass): MachineBadgeProvider => app($providerClass), $this->providerClasses);
    }
}
