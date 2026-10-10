<?php

namespace Functional\Certification\Exceptions;

use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Enums\DispatchFailureReason;
use Functional\Fleet\Exceptions\RefusalException;

final class CertificateNotDeliveredException extends RefusalException
{
    public static function because(CertificateStatus $status, ?DispatchFailureReason $failureReason = null): self
    {
        return new self(
            "The VGP certificate is not delivered (status [{$status->value}]).",
            "certification::certificates.refusals.not_delivered.{$status->value}",
            ['reason' => $failureReason?->label() ?? ''],
        );
    }

    public static function notOpenedYet(): self
    {
        return self::because(CertificateStatus::Pending);
    }
}
