<?php

namespace Functional\Certification\Enums;

enum CertificationHistoryEvent: string
{
    case VgpReportDeposited = 'vgp_report_deposited';
    case CertificateSent = 'certificate_sent';
    case CertificateFailed = 'certificate_failed';
    case CertificateHandDelivered = 'certificate_hand_delivered';
    case CustomerEmailUpdated = 'customer_email_updated';

    /**
     * @param  array<string, string|int|null>  $details
     */
    public function description(array $details): string
    {
        return __("certification::history.{$this->value}", array_map(fn (string|int|null $detail): string => (string) $detail, $details));
    }
}
