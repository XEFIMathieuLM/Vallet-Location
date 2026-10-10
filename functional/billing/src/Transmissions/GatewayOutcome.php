<?php

namespace Functional\Billing\Transmissions;

use Functional\Billing\Enums\TransmissionFailureReason;

final readonly class GatewayOutcome
{
    private function __construct(
        public ?string $externalRef,
        public ?TransmissionFailureReason $failureReason,
        public ?string $message,
    ) {}

    public static function accepted(string $externalRef): self
    {
        return new self($externalRef, null, null);
    }

    public static function rejected(string $reason): self
    {
        return new self(null, TransmissionFailureReason::Rejected, $reason);
    }

    public static function unreachable(string $message): self
    {
        return new self(null, null, $message);
    }
}
