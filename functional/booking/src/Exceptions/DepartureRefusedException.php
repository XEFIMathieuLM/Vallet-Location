<?php

namespace Functional\Booking\Exceptions;

use Carbon\CarbonImmutable;
use Functional\Fleet\Exceptions\RefusalException;
use Functional\Fleet\Models\Machine;

final class DepartureRefusedException extends RefusalException
{
    public static function beforeStartDate(CarbonImmutable $startDate): self
    {
        return new self(__('booking::reservations.refusals.departure_before_start', [
            'date' => $startDate->format('d/m/Y'),
        ]));
    }

    public static function becauseOfMachineStatus(Machine $machine): self
    {
        return new self(__('booking::reservations.refusals.departure_machine_status', [
            'status' => $machine->status->label(),
        ]));
    }

    public static function becauseOfVgp(Machine $machine): self
    {
        if ($machine->vgp_due_date === null) {
            return new self(__('booking::reservations.refusals.departure_vgp_missing'));
        }

        return new self(__('booking::reservations.refusals.departure_vgp_expires', [
            'date' => $machine->vgp_due_date->format('d/m/Y'),
        ]));
    }
}
