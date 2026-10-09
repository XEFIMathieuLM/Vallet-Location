<?php

namespace Functional\Fleet\Enums;

enum MachineStatus: string
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
}
