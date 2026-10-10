<?php

namespace Functional\Certification\States;

use Functional\Certification\Exceptions\IllegalCertificateTransitionException;

trait RefusesCertificateTransitions
{
    public function awaitEmail(): CertificateState
    {
        throw IllegalCertificateTransitionException::for($this, 'await_email');
    }

    public function queue(): CertificateState
    {
        throw IllegalCertificateTransitionException::for($this, 'queue');
    }

    public function send(): CertificateState
    {
        throw IllegalCertificateTransitionException::for($this, 'send');
    }

    public function fail(): CertificateState
    {
        throw IllegalCertificateTransitionException::for($this, 'fail');
    }

    public function handDeliver(): CertificateState
    {
        throw IllegalCertificateTransitionException::for($this, 'hand_deliver');
    }
}
