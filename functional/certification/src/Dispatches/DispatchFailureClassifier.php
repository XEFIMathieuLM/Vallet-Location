<?php

namespace Functional\Certification\Dispatches;

use Functional\Certification\Enums\DispatchFailureReason;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Throwable;

final class DispatchFailureClassifier
{
    private const RECIPIENT_REJECTION_CODES = [550, 551, 553, 554];

    public function classify(Throwable $failure): ?DispatchFailureReason
    {
        return match (true) {
            $failure instanceof RfcComplianceException => DispatchFailureReason::InvalidAddress,
            $failure instanceof TransportExceptionInterface && $this->isRecipientRejection($failure) => DispatchFailureReason::RecipientRejected,
            $failure instanceof TransportExceptionInterface => DispatchFailureReason::MailServiceUnavailable,
            default => null,
        };
    }

    private function isRecipientRejection(Throwable $failure): bool
    {
        foreach (self::RECIPIENT_REJECTION_CODES as $rejectionCode) {
            if ($failure->getCode() === $rejectionCode || str_contains($failure->getMessage(), "\"{$rejectionCode}\"")) {
                return true;
            }
        }

        return false;
    }
}
