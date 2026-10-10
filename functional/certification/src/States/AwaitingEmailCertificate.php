<?php

namespace Functional\Certification\States;

use Functional\Certification\Enums\CertificateStatus;

final class AwaitingEmailCertificate implements CertificateState
{
    use RefusesCertificateTransitions;

    public function status(): CertificateStatus
    {
        return CertificateStatus::AwaitingEmail;
    }

    public function queue(): CertificateState
    {
        return new PendingCertificate;
    }

    public function handDeliver(): CertificateState
    {
        return new HandDeliveredCertificate;
    }
}
