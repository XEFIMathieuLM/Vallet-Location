<?php

namespace Functional\Booking\Extensions;

use Functional\Booking\Enums\ReservationTransition;

final class ReservationDetailSections
{
    /**
     * @var array<string, int>
     */
    private array $positionsByComponent = [];

    /**
     * @var array<string, list<ReservationTransition>>
     */
    private array $guardedTransitionsByComponent = [];

    public function register(string $livewireComponent, int $position, ReservationTransition ...$guardedTransitions): void
    {
        $this->positionsByComponent[$livewireComponent] = $position;
        $this->guardedTransitionsByComponent[$livewireComponent] = array_values($guardedTransitions);
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

    /**
     * @return list<string>
     */
    public function sectionsGuarding(ReservationTransition $transition): array
    {
        return array_keys(array_filter(
            $this->guardedTransitionsByComponent,
            fn (array $guardedTransitions): bool => in_array($transition, $guardedTransitions, true),
        ));
    }

    public function isGuardedBy(string $livewireComponent, ReservationTransition $transition): bool
    {
        return in_array($transition, $this->guardedTransitionsByComponent[$livewireComponent] ?? [], true);
    }
}
