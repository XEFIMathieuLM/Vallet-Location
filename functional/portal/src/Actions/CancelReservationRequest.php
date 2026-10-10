<?php

namespace Functional\Portal\Actions;

use Functional\Portal\Access\Controls\ReservationRequestControl;
use Functional\Portal\Enums\PortalHistoryEvent;
use Functional\Portal\Events\ReservationRequestChanged;
use Functional\Portal\History\PortalHistory;
use Functional\Portal\Models\CustomerAccount;
use Functional\Portal\Models\ReservationRequest;
use Illuminate\Support\Facades\DB;

final class CancelReservationRequest
{
    public function __construct(private readonly PortalHistory $portalHistory) {}

    public function handle(ReservationRequest $reservationRequest, CustomerAccount $account): ReservationRequest
    {
        $cancelled = DB::transaction(function () use ($reservationRequest, $account): ReservationRequest {
            $locked = ReservationRequestControl::ownedBy($account)
                ->whereKey($reservationRequest->id)
                ->lockForUpdate()
                ->firstOrFail();
            $locked->update(['status' => $locked->state()->cancel()->status(), 'decided_at' => now()]);
            $this->portalHistory->record($locked, PortalHistoryEvent::RequestCancelled, $account);

            return $locked;
        });

        ReservationRequestChanged::dispatch($cancelled);

        return $cancelled;
    }
}
