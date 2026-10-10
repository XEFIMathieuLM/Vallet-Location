<?php

namespace Functional\Billing\Transmissions;

use Carbon\CarbonImmutable;
use Functional\Billing\Enums\TransmissionFailureReason;
use Functional\Billing\History\BillingHistory;
use Functional\Billing\Models\BillingExport;
use Functional\Billing\Models\Transmission;

final class TransmissionLifecycle
{
    public function __construct(private readonly BillingHistory $billingHistory) {}

    public function markSent(Transmission $transmission, string $externalRef): void
    {
        $transmission->update([
            'status' => $transmission->state()->send()->status(),
            'external_ref' => $externalRef,
            'sent_at' => CarbonImmutable::now(),
            'failure_reason' => null,
            'last_error' => null,
            'reserved_until' => null,
        ]);
        $this->billingHistory->record($transmission->reservation, 'sent', ['uuid' => $transmission->uuid, 'external_ref' => $externalRef]);
    }

    public function markFailed(Transmission $transmission, TransmissionFailureReason $failureReason, string $message): void
    {
        $transmission->update([
            'status' => $transmission->state()->fail()->status(),
            'failure_reason' => $failureReason,
            'last_error' => $message,
            'reserved_until' => null,
        ]);
        $this->billingHistory->record($transmission->reservation, 'failed', ['uuid' => $transmission->uuid, 'reason' => $message]);
    }

    public function scheduleRetry(Transmission $transmission, string $message): void
    {
        $retryDelays = config()->array('billing.retry_delays_minutes');
        $delayMinutes = $retryDelays[min($transmission->attempts, count($retryDelays)) - 1];

        $transmission->update(['next_attempt_at' => CarbonImmutable::now()->addMinutes($delayMinutes), 'last_error' => $message, 'reserved_until' => null]);
        $this->billingHistory->record($transmission->reservation, 'unreachable', ['uuid' => $transmission->uuid, 'delay' => $delayMinutes]);
    }

    public function requeue(Transmission $transmission): void
    {
        $transmission->update([
            'status' => $transmission->state()->requeue()->status(),
            'next_attempt_at' => CarbonImmutable::now(),
            'failure_reason' => null,
        ]);
        $this->billingHistory->record($transmission->reservation, 'retried', ['uuid' => $transmission->uuid]);
    }

    public function markExported(Transmission $transmission, BillingExport $billingExport): void
    {
        $transmission->update([
            'status' => $transmission->state()->export()->status(),
            'billing_export_id' => $billingExport->id,
        ]);
        $this->billingHistory->record($transmission->reservation, 'exported', ['uuid' => $transmission->uuid, 'export' => $billingExport->id]);
    }
}
