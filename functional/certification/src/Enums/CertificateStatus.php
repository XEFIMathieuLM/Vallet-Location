<?php

namespace Functional\Certification\Enums;

use Functional\Fleet\Contracts\HasLabel;

enum CertificateStatus: string implements HasLabel
{
    case AwaitingReport = 'awaiting_report';
    case AwaitingEmail = 'awaiting_email';
    case Pending = 'pending';
    case Failed = 'failed';
    case Sent = 'sent';
    case HandDelivered = 'hand_delivered';

    public function label(): string
    {
        return __("certification::enums.certificate_status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::AwaitingReport, self::AwaitingEmail => 'amber',
            self::Pending => 'blue',
            self::Failed => 'red',
            self::Sent, self::HandDelivered => 'green',
        };
    }

    public function isDelivered(): bool
    {
        return $this === self::Sent || $this === self::HandDelivered;
    }
}
