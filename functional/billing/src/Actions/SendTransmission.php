<?php

namespace Functional\Billing\Actions;

use Carbon\CarbonImmutable;
use Functional\Billing\Contracts\BillingGateway;
use Functional\Billing\Enums\TransmissionFailureReason;
use Functional\Billing\Exceptions\BillingSoftwareRejectedException;
use Functional\Billing\Exceptions\BillingSoftwareUnreachableException;
use Functional\Billing\Lines\BillableLine;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Transmissions\GatewayOutcome;
use Functional\Billing\Transmissions\TransmissionLifecycle;
use Functional\Billing\Transmissions\TransmissionReservation;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SendTransmission
{
    public function __construct(
        private readonly BillingGateway $billingGateway,
        private readonly MakeBillableLine $makeBillableLine,
        private readonly TransmissionLifecycle $transmissionLifecycle,
        private readonly TransmissionReservation $transmissionReservation,
    ) {}

    public function handle(Transmission $transmission): void
    {
        $billableLine = DB::transaction(fn (): ?BillableLine => $this->reserve($transmission));

        if ($billableLine === null) {
            return;
        }

        $gatewayOutcome = rescue(
            fn (): GatewayOutcome => GatewayOutcome::accepted($this->billingGateway->send($billableLine)),
            fn (Throwable $exception): GatewayOutcome => $this->classify($exception),
            false,
        );

        DB::transaction(fn () => $this->settle($transmission, $gatewayOutcome));
    }

    private function reserve(Transmission $transmission): ?BillableLine
    {
        $lockedTransmission = Transmission::query()->lockForUpdate()->findOrFail($transmission->id);

        if (! $lockedTransmission->state()->canBeSent() || $lockedTransmission->isReserved()) {
            return null;
        }

        $billableLine = $this->makeBillableLine->handle($lockedTransmission);
        $lockedTransmission->update([
            'attempts' => $lockedTransmission->attempts + 1,
            'last_attempt_at' => CarbonImmutable::now(),
            'reserved_until' => $this->transmissionReservation->expiresAt(),
        ]);

        if ($billableLine->customerRef() === null) {
            $this->transmissionLifecycle->markFailed($lockedTransmission, TransmissionFailureReason::CustomerUnknown, TransmissionFailureReason::CustomerUnknown->label());

            return null;
        }

        return $billableLine;
    }

    private function classify(Throwable $exception): GatewayOutcome
    {
        return match (true) {
            $exception instanceof BillingSoftwareRejectedException => GatewayOutcome::rejected($exception->getMessage()),
            $exception instanceof BillingSoftwareUnreachableException => GatewayOutcome::unreachable($exception->getMessage()),
            default => throw $exception,
        };
    }

    private function settle(Transmission $transmission, GatewayOutcome $gatewayOutcome): void
    {
        $lockedTransmission = Transmission::query()->lockForUpdate()->findOrFail($transmission->id);

        if (! $lockedTransmission->state()->canBeSent()) {
            return;
        }

        match (true) {
            $gatewayOutcome->externalRef !== null => $this->transmissionLifecycle->markSent($lockedTransmission, $gatewayOutcome->externalRef),
            $gatewayOutcome->failureReason !== null => $this->transmissionLifecycle->markFailed($lockedTransmission, $gatewayOutcome->failureReason, (string) $gatewayOutcome->message),
            default => $this->transmissionLifecycle->scheduleRetry($lockedTransmission, (string) $gatewayOutcome->message),
        };
    }
}
