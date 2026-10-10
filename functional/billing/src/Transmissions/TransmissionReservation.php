<?php

namespace Functional\Billing\Transmissions;

use Carbon\CarbonImmutable;

final class TransmissionReservation
{
    public function seconds(): int
    {
        return config()->integer('billing.gateway_timeout_seconds') + config()->integer('billing.reservation_margin_seconds');
    }

    public function expiresAt(): CarbonImmutable
    {
        return CarbonImmutable::now()->addSeconds($this->seconds());
    }
}
