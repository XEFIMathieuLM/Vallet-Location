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
            self::Reserved => 'bg-black text-white dark:bg-white dark:text-black',
            self::Workshop => 'bg-amber-300 text-black',
            self::OutOfOrder => 'bg-red-500 text-black',
            self::VgpInvalid => 'bg-zinc-200 text-zinc-800 dark:bg-zinc-600 dark:text-white',
        };
    }
}
