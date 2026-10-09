<?php

namespace Functional\Fleet\States;

use Functional\Fleet\Enums\MachineStatus;

interface MachineState
{
    public function status(): MachineStatus;

    public function acceptsReservations(): bool;

    public function depart(): MachineState;

    public function returnInGoodState(): MachineState;

    public function returnToWorkshop(): MachineState;

    public function sendToWorkshop(): MachineState;

    public function markOutOfOrder(): MachineState;

    public function makeAvailable(): MachineState;

    public function retire(): MachineState;
}
