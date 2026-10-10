<?php

namespace Functional\Certification\States;

use Functional\Certification\Enums\CertificateStatus;

final class SentCertificate implements CertificateState
{
    use RefusesCertificateTransitions;

    public function status(): CertificateStatus
    {
        return CertificateStatus::Sent;
    }
}
