<?php

namespace Functional\Booking\Enums;

use Functional\Fleet\Enums\MachineTransition;

enum ReturnCondition: string
{
    case GoodState = 'good_state';
    case Workshop = 'workshop';

    public function machineTransition(): MachineTransition
    {
        return match ($this) {
            self::GoodState => MachineTransition::ReturnInGoodState,
            self::Workshop => MachineTransition::ReturnToWorkshop,
        };
    }

    public function label(): string
    {
        return __("booking::reservations.return_conditions.{$this->value}");
    }
}
