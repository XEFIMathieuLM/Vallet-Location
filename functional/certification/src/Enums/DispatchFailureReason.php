<?php

namespace Functional\Certification\Enums;

use Functional\Fleet\Contracts\HasLabel;

enum DispatchFailureReason: string implements HasLabel
{
    case InvalidAddress = 'invalid_address';
    case RecipientRejected = 'recipient_rejected';
    case MailServiceUnavailable = 'mail_service_unavailable';

    public function label(): string
    {
        return __("certification::enums.dispatch_failure_reason.{$this->value}");
    }

    public function isPermanent(): bool
    {
        return $this !== self::MailServiceUnavailable;
    }
}
