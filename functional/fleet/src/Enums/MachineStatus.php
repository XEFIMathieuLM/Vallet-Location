<?php

namespace Functional\Fleet\Enums;

use Functional\Fleet\Contracts\HasLabel;

enum MachineStatus: string implements HasLabel
{
    case Available = 'available';
    case RentedOut = 'rented_out';
    case Workshop = 'workshop';
    case OutOfOrder = 'out_of_order';
    case Retired = 'retired';

    public function label(): string
    {
        return __("fleet::machines.status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'green',
            self::RentedOut => 'blue',
            self::Workshop => 'amber',
            self::OutOfOrder => 'red',
            self::Retired => 'zinc',
        };
    }
}
