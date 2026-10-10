<?php

namespace Functional\Billing\States;

use Functional\Billing\Enums\TransmissionStatus;

final class PendingTransmission implements TransmissionState
{
    use RefusesTransmissionTransitions;

    public function status(): TransmissionStatus
    {
        return TransmissionStatus::Pending;
    }

    public function send(): TransmissionState
    {
        return new SentTransmission;
    }

    public function fail(): TransmissionState
    {
        return new FailedTransmission;
    }

    public function export(): TransmissionState
    {
        return new ExportedTransmission;
    }

    public function canBeSent(): bool
    {
        return true;
    }

    public function canBeExported(): bool
    {
        return true;
    }
}
