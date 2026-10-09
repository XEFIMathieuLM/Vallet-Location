<?php

namespace Functional\Fleet\States;

use Functional\Fleet\Exceptions\IllegalMachineTransitionException;

trait RefusesMachineTransitions
{
    public function depart(): MachineState
    {
        throw IllegalMachineTransitionException::for($this, 'depart');
    }

    public function returnInGoodState(): MachineState
    {
        throw IllegalMachineTransitionException::for($this, 'return_in_good_state');
    }

    public function returnToWorkshop(): MachineState
    {
        throw IllegalMachineTransitionException::for($this, 'return_to_workshop');
    }

    public function sendToWorkshop(): MachineState
    {
        throw IllegalMachineTransitionException::for($this, 'send_to_workshop');
    }

    public function markOutOfOrder(): MachineState
    {
        throw IllegalMachineTransitionException::for($this, 'mark_out_of_order');
    }

    public function makeAvailable(): MachineState
    {
        throw IllegalMachineTransitionException::for($this, 'make_available');
    }

    public function retire(): MachineState
    {
        throw IllegalMachineTransitionException::for($this, 'retire');
    }
}
