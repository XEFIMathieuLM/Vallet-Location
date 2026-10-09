<?php

namespace Functional\Booking\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Fleet\Models\Machine;

final class MachineNotReservableException extends RefusalException
{
    public static function becauseOfStatus(Machine $machine): self
    {
        return new self(__('booking::reservations.refusals.machine_status', [
            'status' => $machine->status->label(),
        ]));
    }

    public static function becauseOfVgp(Machine $machine): self
    {
        if ($machine->vgp_due_date === null) {
            return new self(__('booking::reservations.refusals.vgp_missing'));
        }

        return new self(__('booking::reservations.refusals.vgp_expires', [
            'date' => $machine->vgp_due_date->format('d/m/Y'),
        ]));
    }

    public static function becauseNotReturned(): self
    {
        return new self(__('booking::reservations.refusals.machine_not_returned'));
    }
}
