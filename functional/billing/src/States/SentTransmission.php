<?php

namespace Functional\Billing\States;

use Functional\Billing\Enums\TransmissionStatus;

final class SentTransmission implements TransmissionState
{
    use RefusesTransmissionTransitions;

    public function status(): TransmissionStatus
    {
        return TransmissionStatus::Sent;
    }
}
