<?php

namespace Functional\Booking\Exceptions;

use Carbon\CarbonImmutable;
use Functional\Fleet\Exceptions\RefusalException;
use Functional\Fleet\Models\Machine;

final class DepartureRefusedException extends RefusalException
{
    public static function beforeStartDate(CarbonImmutable $startDate): self
    {
        return new self(
            "Departure is not allowed before the start date {$startDate->toDateString()}.",
            'booking::reservations.refusals.departure_before_start',
            ['date' => $startDate->format('d/m/Y')],
        );
    }

    public static function becauseOfMachineStatus(Machine $machine): self
    {
        return new self(
            "Departure refused: machine {$machine->reference} has status {$machine->status->value}.",
            'booking::reservations.refusals.departure_machine_status',
            ['status' => $machine->status],
        );
    }

    public static function becauseOfVgp(Machine $machine): self
    {
        if ($machine->vgp_due_date === null) {
            return new self(
                "Departure refused: machine {$machine->reference} has no VGP due date.",
                'booking::reservations.refusals.departure_vgp_missing',
            );
        }

        return new self(
            "Departure refused: VGP of machine {$machine->reference} expires on {$machine->vgp_due_date->toDateString()}.",
            'booking::reservations.refusals.departure_vgp_expires',
            ['date' => $machine->vgp_due_date->format('d/m/Y')],
        );
    }
}
