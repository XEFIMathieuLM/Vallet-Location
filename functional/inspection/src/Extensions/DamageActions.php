<?php

namespace Functional\Inspection\Extensions;

class DamageActions
{
    /**
     * @var array<string, int>
     */
    private array $components = [];

    public function register(string $livewireComponent, int $position): void
    {
        $this->components[$livewireComponent] = $position;
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        $components = $this->components;
        asort($components);

        return array_keys($components);
    }

    public function isEmpty(): bool
    {
        return $this->components === [];
    }
}
