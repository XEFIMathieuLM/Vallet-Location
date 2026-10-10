<?php

namespace Functional\Booking\Extensions;

final class ReservationDetailSections
{
    /**
     * @var array<string, int>
     */
    private array $positionsByComponent = [];

    public function register(string $livewireComponent, int $position): void
    {
        $this->positionsByComponent[$livewireComponent] = $position;
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        $positionsByComponent = $this->positionsByComponent;
        asort($positionsByComponent);

        return array_keys($positionsByComponent);
    }

    public function isEmpty(): bool
    {
        return $this->positionsByComponent === [];
    }
}
