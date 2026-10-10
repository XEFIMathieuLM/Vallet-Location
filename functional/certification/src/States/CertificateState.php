<?php

namespace Functional\Certification\States;

use Functional\Certification\Enums\CertificateStatus;

interface CertificateState
{
    public function status(): CertificateStatus;

    public function awaitEmail(): CertificateState;

    public function queue(): CertificateState;

    public function send(): CertificateState;

    public function fail(): CertificateState;

    public function handDeliver(): CertificateState;
}
