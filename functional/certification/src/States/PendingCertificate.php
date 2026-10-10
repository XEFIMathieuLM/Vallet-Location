<?php

namespace Functional\Certification\States;

use Functional\Certification\Enums\CertificateStatus;

final class PendingCertificate implements CertificateState
{
    use RefusesCertificateTransitions;

    public function status(): CertificateStatus
    {
        return CertificateStatus::Pending;
    }

    public function send(): CertificateState
    {
        return new SentCertificate;
    }

    public function fail(): CertificateState
    {
        return new FailedCertificate;
    }

    public function handDeliver(): CertificateState
    {
        return new HandDeliveredCertificate;
    }
}
