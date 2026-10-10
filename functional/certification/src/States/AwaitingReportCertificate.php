<?php

namespace Functional\Certification\States;

use Functional\Certification\Enums\CertificateStatus;

final class AwaitingReportCertificate implements CertificateState
{
    use RefusesCertificateTransitions;

    public function status(): CertificateStatus
    {
        return CertificateStatus::AwaitingReport;
    }

    public function awaitEmail(): CertificateState
    {
        return new AwaitingEmailCertificate;
    }

    public function queue(): CertificateState
    {
        return new PendingCertificate;
    }
}
