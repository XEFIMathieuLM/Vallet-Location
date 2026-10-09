<?php

namespace Functional\Booking\Enums;

enum ConflictReason: string
{
    case MachineUnavailable = 'machine_unavailable';
    case VgpExpired = 'vgp_expired';
    case MachineNotReturned = 'machine_not_returned';

    public function label(): string
    {
        return __("booking::reservations.conflicts.{$this->value}");
    }
}
