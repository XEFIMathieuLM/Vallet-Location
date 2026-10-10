<?php

namespace Functional\Billing\States;

use Functional\Billing\Enums\TransmissionStatus;

final class TransmissionStateFactory
{
    public static function fromStatus(TransmissionStatus $status): TransmissionState
    {
        return match ($status) {
            TransmissionStatus::Pending => new PendingTransmission,
            TransmissionStatus::Sent => new SentTransmission,
            TransmissionStatus::Exported => new ExportedTransmission,
            TransmissionStatus::Failed => new FailedTransmission,
        };
    }
}
