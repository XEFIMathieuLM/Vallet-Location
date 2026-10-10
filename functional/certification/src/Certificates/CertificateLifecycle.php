<?php

namespace Functional\Certification\Certificates;

use Carbon\CarbonImmutable;
use Functional\Certification\Events\CertificateChanged;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\States\CertificateState;

final class CertificateLifecycle
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function moveTo(ReservationCertificate $certificate, CertificateState $nextState, array $attributes = []): void
    {
        $now = CarbonImmutable::now();

        $certificate->update([
            ...$attributes,
            'status' => $nextState->status(),
            'status_changed_at' => $now,
            'delivered_at' => $nextState->status()->isDelivered() ? $now : null,
        ]);

        CertificateChanged::dispatch($certificate);
    }
}
