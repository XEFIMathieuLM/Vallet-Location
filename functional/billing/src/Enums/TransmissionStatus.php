<?php

namespace Functional\Billing\Enums;

enum TransmissionStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Exported = 'exported';
    case Failed = 'failed';

    public function label(): string
    {
        return __("billing::enums.transmission_status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Sent => 'green',
            self::Exported => 'blue',
            self::Failed => 'red',
        };
    }
}
