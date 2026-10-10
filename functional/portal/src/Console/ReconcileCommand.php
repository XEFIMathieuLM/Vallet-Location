<?php

namespace Functional\Portal\Console;

use Carbon\CarbonImmutable;
use Functional\Portal\Actions\ExpireReservationRequest;
use Functional\Portal\Enums\ReservationRequestStatus;
use Functional\Portal\Jobs\NotifyRequestDecisionJob;
use Functional\Portal\Models\ReservationRequest;
use Illuminate\Console\Command;

final class ReconcileCommand extends Command
{
    protected $signature = 'portal:reconcile';

    protected $description = 'Expire the online requests left pending after their start date and resend the decision e-mails that never left';

    public function handle(ExpireReservationRequest $expireReservationRequest): int
    {
        ReservationRequest::query()
            ->where('status', ReservationRequestStatus::Pending)
            ->whereDate('start_date', '<', CarbonImmutable::today())
            ->pluck('id')
            ->each(fn (int $reservationRequestId) => $expireReservationRequest->handle($reservationRequestId));

        ReservationRequest::query()
            ->whereIn('status', [ReservationRequestStatus::Confirmed, ReservationRequestStatus::Refused, ReservationRequestStatus::Expired])
            ->whereNull('customer_notified_at')
            ->where('decided_at', '<=', CarbonImmutable::now()->subMinutes(config()->integer('portal.notification_retry_after_minutes')))
            ->pluck('id')
            ->each(fn (int $reservationRequestId) => NotifyRequestDecisionJob::dispatch($reservationRequestId));

        return self::SUCCESS;
    }
}
