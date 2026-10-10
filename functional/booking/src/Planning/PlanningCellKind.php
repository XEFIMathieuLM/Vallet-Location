<?php

namespace Functional\Booking\Planning;

enum PlanningCellKind: string
{
    case Free = 'free';
    case Reserved = 'reserved';
    case Workshop = 'workshop';
    case OutOfOrder = 'out_of_order';
    case VgpInvalid = 'vgp_invalid';

    public function label(): string
    {
        return __("booking::reservations.planning.cells.{$this->value}");
    }

    public function shortLabel(): string
    {
        return __("booking::reservations.planning.short_labels.{$this->value}");
    }

    public function cssClasses(): string
    {
        return match ($this) {
            self::Free => 'bg-transparent',
            self::Reserved => 'bg-blue-600 text-white',
            self::Workshop => 'bg-amber-300 text-zinc-900',
            self::OutOfOrder => 'bg-red-600 text-white',
            self::VgpInvalid => 'bg-zinc-300 text-zinc-900 dark:bg-zinc-500 dark:text-white',
        };
    }
}
