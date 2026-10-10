<?php

namespace Functional\Portal\Actions;

use Functional\Fleet\Contracts\AgencyMember;
use Functional\Portal\Enums\PortalHistoryEvent;
use Functional\Portal\Events\ReservationRequestChanged;
use Functional\Portal\Exceptions\RefusalReasonRequiredException;
use Functional\Portal\History\PortalHistory;
use Functional\Portal\Jobs\NotifyRequestDecisionJob;
use Functional\Portal\Models\ReservationRequest;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

final class RefuseReservationRequest
{
    public function __construct(private readonly PortalHistory $portalHistory) {}

    public function handle(ReservationRequest $reservationRequest, string $reason, Authenticatable&AgencyMember $author): ReservationRequest
    {
        $trimmedReason = $this->validReason($reason);

        $refused = DB::transaction(function () use ($reservationRequest, $trimmedReason, $author): ReservationRequest {
            $locked = ReservationRequest::query()->whereKey($reservationRequest->id)->lockForUpdate()->firstOrFail();
            $locked->update([
                'status' => $locked->state()->refuse()->status(),
                'refusal_reason' => $trimmedReason,
                'decided_by' => $author->getKey(),
                'decided_agency_id' => $author->agencyId(),
                'decided_at' => now(),
            ]);
            $this->portalHistory->record($locked, PortalHistoryEvent::RequestRefused, $author, ['reason' => $trimmedReason]);

            return $locked;
        });

        ReservationRequestChanged::dispatch($refused);
        NotifyRequestDecisionJob::dispatch($refused->id)->afterCommit();

        return $refused;
    }

    private function validReason(string $reason): string
    {
        $trimmedReason = trim($reason);
        $maxLength = config()->integer('portal.refusal_reason_max_length');

        if ($trimmedReason === '') {
            throw RefusalReasonRequiredException::missing();
        }

        if (mb_strlen($trimmedReason) > $maxLength) {
            throw RefusalReasonRequiredException::tooLong($maxLength);
        }

        return $trimmedReason;
    }
}
