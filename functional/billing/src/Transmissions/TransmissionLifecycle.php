<?php

namespace Functional\Billing\Transmissions;

use Carbon\CarbonImmutable;
use Functional\Billing\Enums\BillableLineType;
use Functional\Billing\Enums\BillingHistoryEvent;
use Functional\Billing\Enums\TransmissionFailureReason;
use Functional\Billing\Exceptions\TransmissionWithoutSourceException;
use Functional\Billing\Extensions\BillableSources;
use Functional\Billing\History\BillingHistory;
use Functional\Billing\Models\BillingExport;
use Functional\Billing\Models\Transmission;
use Illuminate\Database\Eloquent\Model;

final class TransmissionLifecycle
{
    public function __construct(
        private readonly BillingHistory $billingHistory,
        private readonly BillableSources $billableSources,
    ) {}

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
        $this->billingHistory->record($this->historySubject($transmission), BillingHistoryEvent::Sent, ['uuid' => $transmission->uuid, 'external_ref' => $externalRef]);
    }

    public function markFailed(Transmission $transmission, TransmissionFailureReason $failureReason, string $message): void
    {
        $transmission->update([
            'status' => $transmission->state()->fail()->status(),
            'failure_reason' => $failureReason,
            'last_error' => $message,
            'reserved_until' => null,
        ]);
        $this->billingHistory->record($this->historySubject($transmission), BillingHistoryEvent::Failed, ['uuid' => $transmission->uuid, 'reason' => $message]);
    }

    public function scheduleRetry(Transmission $transmission, string $message): void
    {
        $retryDelays = config()->array('billing.retry_delays_minutes');
        $delayMinutes = $retryDelays[min($transmission->attempts, count($retryDelays)) - 1];

        $transmission->update(['next_attempt_at' => CarbonImmutable::now()->addMinutes($delayMinutes), 'last_error' => $message, 'reserved_until' => null]);
        $this->billingHistory->record($this->historySubject($transmission), BillingHistoryEvent::Unreachable, ['uuid' => $transmission->uuid, 'delay' => $delayMinutes]);
    }

    public function requeue(Transmission $transmission): void
    {
        $transmission->update([
            'status' => $transmission->state()->requeue()->status(),
            'next_attempt_at' => CarbonImmutable::now(),
            'failure_reason' => null,
        ]);
        $this->billingHistory->record($this->historySubject($transmission), BillingHistoryEvent::Retried, ['uuid' => $transmission->uuid]);
    }

    public function markExported(Transmission $transmission, BillingExport $billingExport): void
    {
        $transmission->update([
            'status' => $transmission->state()->export()->status(),
            'billing_export_id' => $billingExport->id,
        ]);
        $this->billingHistory->record($this->historySubject($transmission), BillingHistoryEvent::Exported, ['uuid' => $transmission->uuid, 'export' => $billingExport->id]);
    }

    private function historySubject(Transmission $transmission): Model
    {
        if ($transmission->source_type instanceof BillableLineType) {
            return $this->billableSources->for($transmission->source_type)->subjects(collect([$transmission]))[$transmission->id]->historySubject;
        }

        return $transmission->reservation ?? throw TransmissionWithoutSourceException::for($transmission->id);
    }
}
