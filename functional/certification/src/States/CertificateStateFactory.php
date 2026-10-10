<?php

namespace Functional\Certification\States;

use Functional\Certification\Enums\CertificateStatus;

final class CertificateStateFactory
{
    public static function fromStatus(CertificateStatus $status): CertificateState
    {
        return match ($status) {
            CertificateStatus::AwaitingReport => new AwaitingReportCertificate,
            CertificateStatus::AwaitingEmail => new AwaitingEmailCertificate,
            CertificateStatus::Pending => new PendingCertificate,
            CertificateStatus::Failed => new FailedCertificate,
            CertificateStatus::Sent => new SentCertificate,
            CertificateStatus::HandDelivered => new HandDeliveredCertificate,
        };
    }
}
