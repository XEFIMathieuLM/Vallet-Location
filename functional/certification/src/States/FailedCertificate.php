<?php

namespace Functional\Certification\States;

use Functional\Certification\Enums\CertificateStatus;

final class FailedCertificate implements CertificateState
{
    use RefusesCertificateTransitions;

    public function status(): CertificateStatus
    {
        return CertificateStatus::Failed;
    }

    public function queue(): CertificateState
    {
        return new PendingCertificate;
    }

    public function send(): CertificateState
    {
        return new SentCertificate;
    }

    public function handDeliver(): CertificateState
    {
        return new HandDeliveredCertificate;
    }
}
