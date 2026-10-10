<?php

namespace Functional\Portal\Actions;

use Functional\Portal\Enums\PortalHistoryEvent;
use Functional\Portal\Events\ReservationRequestChanged;
use Functional\Portal\History\PortalHistory;
use Functional\Portal\Jobs\NotifyRequestDecisionJob;
use Functional\Portal\Models\ReservationRequest;
use Illuminate\Support\Facades\DB;

final class ExpireReservationRequest
{
    public function __construct(private readonly PortalHistory $portalHistory) {}

    public function handle(int $reservationRequestId): void
    {
        $expired = DB::transaction(function () use ($reservationRequestId): ?ReservationRequest {
            $locked = ReservationRequest::query()->whereKey($reservationRequestId)->lockForUpdate()->firstOrFail();

            if (! $locked->state()->isOpen()) {
                return null;
            }

            $locked->update(['status' => $locked->state()->expire()->status(), 'decided_at' => now()]);
            $this->portalHistory->record($locked, PortalHistoryEvent::RequestExpired, null);

            return $locked;
        });

        if ($expired !== null) {
            ReservationRequestChanged::dispatch($expired);
            NotifyRequestDecisionJob::dispatch($expired->id)->afterCommit();
        }
    }
}
