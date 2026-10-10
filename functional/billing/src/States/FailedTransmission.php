<?php

namespace Functional\Billing\States;

use Functional\Billing\Enums\TransmissionStatus;

final class FailedTransmission implements TransmissionState
{
    use RefusesTransmissionTransitions;

    public function status(): TransmissionStatus
    {
        return TransmissionStatus::Failed;
    }

    public function requeue(): TransmissionState
    {
        return new PendingTransmission;
    }

    public function export(): TransmissionState
    {
        return new ExportedTransmission;
    }

    public function canBeExported(): bool
    {
        return true;
    }

    public function canBeRetried(): bool
    {
        return true;
    }
}
