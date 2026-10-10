<?php

namespace Functional\Fleet\Enums;

use Functional\Fleet\States\MachineState;
use Functional\Fleet\States\MachineStateFactory;

enum MachineTransition: string
{
    case Depart = 'depart';
    case ReturnInGoodState = 'return_in_good_state';
    case ReturnToWorkshop = 'return_to_workshop';
    case SendToWorkshop = 'send_to_workshop';
    case MarkOutOfOrder = 'mark_out_of_order';
    case MakeAvailable = 'make_available';
    case Retire = 'retire';

    public function applyTo(MachineState $state): MachineState
    {
        return match ($this) {
            self::Depart => $state->depart(),
            self::ReturnInGoodState => $state->returnInGoodState(),
            self::ReturnToWorkshop => $state->returnToWorkshop(),
            self::SendToWorkshop => $state->sendToWorkshop(),
            self::MarkOutOfOrder => $state->markOutOfOrder(),
            self::MakeAvailable => $state->makeAvailable(),
            self::Retire => $state->retire(),
        };
    }

    public function isManual(): bool
    {
        return in_array($this, [self::SendToWorkshop, self::MarkOutOfOrder, self::MakeAvailable, self::Retire], true);
    }

    /**
     * @return list<self>
     */
    public static function manualFrom(MachineStatus $status): array
    {
        return array_values(array_filter(
            MachineStateFactory::fromStatus($status)->allowedTransitions(),
            fn (self $transition): bool => $transition->isManual(),
        ));
    }

    public function label(): string
    {
        return __("fleet::machines.transitions.{$this->value}");
    }
}
