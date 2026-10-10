<?php

namespace Functional\Booking\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Fleet\Models\Machine;

final class MachineNotReservableException extends RefusalException
{
    public static function becauseOfStatus(Machine $machine): self
    {
        return new self(
            "Machine {$machine->reference} cannot be reserved: status {$machine->status->value}.",
            'booking::reservations.refusals.machine_status',
            ['status' => $machine->status],
        );
    }

    public static function becauseOfVgp(Machine $machine): self
    {
        if ($machine->vgp_due_date === null) {
            return new self(
                "Machine {$machine->reference} cannot be reserved: no VGP due date.",
                'booking::reservations.refusals.vgp_missing',
            );
        }

        return new self(
            "Machine {$machine->reference} cannot be reserved: VGP expires on {$machine->vgp_due_date->toDateString()}.",
            'booking::reservations.refusals.vgp_expires',
            ['date' => $machine->vgp_due_date->format('d/m/Y')],
        );
    }

    public static function becauseNotReturned(Machine $machine): self
    {
        return new self(
            "Machine {$machine->reference} cannot be reserved: its current rental is overdue.",
            'booking::reservations.refusals.machine_not_returned',
        );
    }
}
