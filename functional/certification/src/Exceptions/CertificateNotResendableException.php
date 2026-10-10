<?php

namespace Functional\Certification\Exceptions;

use Functional\Certification\Enums\DispatchFailureReason;
use Functional\Fleet\Exceptions\RefusalException;

final class CertificateNotResendableException extends RefusalException
{
    public static function reservationNotActive(): self
    {
        return new self('The reservation is neither confirmed nor in progress.', 'certification::certificates.refusals.resend.not_active');
    }

    public static function noCertificate(): self
    {
        return new self('The reservation has no VGP certificate.', 'certification::certificates.refusals.resend.no_certificate');
    }

    public static function noReport(): self
    {
        return new self('No VGP report is deposited for the machine.', 'certification::certificates.refusals.resend.no_report');
    }

    public static function noEmail(): self
    {
        return new self('The customer has no e-mail address.', 'certification::certificates.refusals.resend.no_email');
    }

    public static function sendingFailed(DispatchFailureReason $failureReason): self
    {
        return new self("The VGP certificate could not be resent ({$failureReason->value}).", 'certification::certificates.refusals.resend.sending_failed', ['reason' => $failureReason]);
    }
}
