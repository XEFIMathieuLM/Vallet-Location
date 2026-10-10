<?php

namespace Functional\Billing\Actions;

use Carbon\CarbonImmutable;
use Functional\Billing\Contracts\BillingGateway;
use Functional\Billing\Enums\TransmissionFailureReason;
use Functional\Billing\Exceptions\BillingSoftwareRejectedException;
use Functional\Billing\Exceptions\BillingSoftwareUnreachableException;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Support\TransmissionLifecycle;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SendTransmission
{
    public function __construct(
        private readonly BillingGateway $billingGateway,
        private readonly MakeBillableLine $makeBillableLine,
        private readonly TransmissionLifecycle $transmissionLifecycle,
    ) {}

    public function handle(Transmission $transmission): void
    {
        DB::transaction(function () use ($transmission): void {
            $lockedTransmission = Transmission::query()->lockForUpdate()->findOrFail($transmission->id);

            if (! $lockedTransmission->state()->canBeSent()) {
                return;
            }

            $billableLine = $this->makeBillableLine->handle($lockedTransmission);
            $lockedTransmission->update(['attempts' => $lockedTransmission->attempts + 1, 'last_attempt_at' => CarbonImmutable::now()]);

            if ($billableLine->customerRef === null) {
                $this->transmissionLifecycle->markFailed($lockedTransmission, TransmissionFailureReason::CustomerUnknown, TransmissionFailureReason::CustomerUnknown->label());

                return;
            }

            $externalRef = rescue(
                fn (): string => $this->billingGateway->send($billableLine),
                fn (Throwable $exception) => $this->recordFailure($lockedTransmission, $exception),
                false,
            );

            if (is_string($externalRef)) {
                $this->transmissionLifecycle->markSent($lockedTransmission, $externalRef);
            }
        });
    }

    private function recordFailure(Transmission $transmission, Throwable $exception): null
    {
        match (true) {
            $exception instanceof BillingSoftwareRejectedException => $this->transmissionLifecycle->markFailed($transmission, TransmissionFailureReason::Rejected, $exception->getMessage()),
            $exception instanceof BillingSoftwareUnreachableException => $this->transmissionLifecycle->scheduleRetry($transmission, $exception->getMessage()),
            default => throw $exception,
        };

        return null;
    }
}
