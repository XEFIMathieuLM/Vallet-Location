<?php

namespace Functional\Inspection\Enums;

enum RevocationReason: string
{
    case Replaced = 'replaced';
    case StepValidated = 'step_validated';
    case ReservationCancelled = 'reservation_cancelled';
}
