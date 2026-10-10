<?php

namespace Functional\Certification\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;

final class HandDeliveryRefusedException extends RefusalException
{
    public static function noReport(): self
    {
        return new self('No VGP report is deposited for the machine.', 'certification::certificates.refusals.hand_delivery.no_report');
    }

    public static function reservationNotConfirmed(): self
    {
        return new self('The reservation is not confirmed.', 'certification::certificates.refusals.hand_delivery.not_confirmed');
    }

    public static function alreadyDelivered(): self
    {
        return new self('The VGP certificate is already delivered.', 'certification::certificates.refusals.hand_delivery.already_delivered');
    }

    public static function noCertificate(): self
    {
        return new self('The reservation has no VGP certificate.', 'certification::certificates.refusals.hand_delivery.no_certificate');
    }
}
