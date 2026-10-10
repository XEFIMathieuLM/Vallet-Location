<?php

namespace Functional\Billing\States;

use Functional\Billing\Enums\TransmissionStatus;

final class ExportedTransmission implements TransmissionState
{
    use RefusesTransmissionTransitions;

    public function status(): TransmissionStatus
    {
        return TransmissionStatus::Exported;
    }
}
