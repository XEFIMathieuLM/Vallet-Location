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

    public function cssClasses(): string
    {
        return match ($this) {
            self::Free => 'bg-transparent',
            self::Reserved => 'bg-blue-500/80',
            self::Workshop => 'bg-amber-400/80',
            self::OutOfOrder => 'bg-red-500/80',
            self::VgpInvalid => 'bg-zinc-400/60 dark:bg-zinc-500/60',
        };
    }
}
