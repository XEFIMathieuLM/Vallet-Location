<?php

namespace Functional\Fleet\States;

use Functional\Fleet\Enums\MachineTransition;
use Functional\Fleet\Exceptions\IllegalMachineTransitionException;

trait RefusesMachineTransitions
{
    public function depart(): MachineState
    {
        throw IllegalMachineTransitionException::for($this, MachineTransition::Depart);
    }

    public function returnInGoodState(): MachineState
    {
        throw IllegalMachineTransitionException::for($this, MachineTransition::ReturnInGoodState);
    }

    public function returnToWorkshop(): MachineState
    {
        throw IllegalMachineTransitionException::for($this, MachineTransition::ReturnToWorkshop);
    }

    public function sendToWorkshop(): MachineState
    {
        throw IllegalMachineTransitionException::for($this, MachineTransition::SendToWorkshop);
    }

    public function markOutOfOrder(): MachineState
    {
        throw IllegalMachineTransitionException::for($this, MachineTransition::MarkOutOfOrder);
    }

    public function makeAvailable(): MachineState
    {
        throw IllegalMachineTransitionException::for($this, MachineTransition::MakeAvailable);
    }

    public function retire(): MachineState
    {
        throw IllegalMachineTransitionException::for($this, MachineTransition::Retire);
    }
}
