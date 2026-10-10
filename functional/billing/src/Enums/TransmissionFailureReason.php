<?php

namespace Functional\Billing\Enums;

enum TransmissionFailureReason: string
{
    case CustomerUnknown = 'customer_unknown';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __("billing::enums.transmission_failure_reason.{$this->value}");
    }
}
