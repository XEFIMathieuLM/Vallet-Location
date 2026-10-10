<?php

namespace Functional\Portal\Jobs;

use Functional\Portal\Enums\ReservationRequestStatus;
use Functional\Portal\Models\ReservationRequest;
use Functional\Portal\Notifications\ReservationRequestDecided;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

final class NotifyRequestDecisionJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(public readonly int $reservationRequestId) {}

    public function uniqueId(): string
    {
        return (string) $this->reservationRequestId;
    }

    public function handle(): void
    {
        DB::transaction(function (): void {
            $reservationRequest = ReservationRequest::query()
                ->with(['account', 'machine.category', 'machine.agency', 'reservation.machine.category', 'reservation.machine.agency'])
                ->whereKey($this->reservationRequestId)
                ->lockForUpdate()
                ->first();

            if ($reservationRequest === null || ! $this->isToNotify($reservationRequest)) {
                return;
            }

            $reservationRequest->account->notify(new ReservationRequestDecided($reservationRequest));
            $reservationRequest->update(['customer_notified_at' => now()]);
        });
    }

    private function isToNotify(ReservationRequest $reservationRequest): bool
    {
        return $reservationRequest->customer_notified_at === null
            && in_array($reservationRequest->status, [ReservationRequestStatus::Confirmed, ReservationRequestStatus::Refused, ReservationRequestStatus::Expired], true);
    }
}
