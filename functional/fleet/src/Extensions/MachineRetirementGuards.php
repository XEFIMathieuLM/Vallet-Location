<?php

namespace Functional\Fleet\Extensions;

use Functional\Fleet\Contracts\MachineRetirementGuard;

final class MachineRetirementGuards
{
    /**
     * @var list<class-string<MachineRetirementGuard>>
     */
    private array $guardClasses = [];

    /**
     * @param  class-string<MachineRetirementGuard>  $guardClass
     */
    public function register(string $guardClass): void
    {
        $this->guardClasses[] = $guardClass;
    }

    /**
     * @return list<MachineRetirementGuard>
     */
    public function all(): array
    {
        return array_map(fn (string $guardClass): MachineRetirementGuard => app($guardClass), $this->guardClasses);
    }
}
