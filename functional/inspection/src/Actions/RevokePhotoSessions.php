<?php

namespace Functional\Inspection\Actions;

use Carbon\CarbonImmutable;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Enums\RevocationReason;
use Functional\Inspection\Events\PhotoSessionChanged;
use Functional\Inspection\Models\PhotoSession;
use Functional\Inspection\Support\InspectionHistory;

class RevokePhotoSessions
{
    public function __construct(private readonly InspectionHistory $inspectionHistory) {}

    /**
     * @param  list<InspectionStep>  $steps
     */
    public function handle(Reservation $reservation, array $steps, RevocationReason $reason): void
    {
        $now = CarbonImmutable::now();

        $activeSessions = PhotoSession::query()
            ->where('reservation_id', $reservation->id)
            ->whereIn('step', $steps)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', $now)
            ->get(['id', 'step']);

        PhotoSession::query()
            ->whereKey($activeSessions->modelKeys())
            ->update(['revoked_at' => $now, 'revoked_reason' => $reason]);

        $activeSessions->each(fn (PhotoSession $session) => $this->inspectionHistory->record(
            $reservation,
            'photo_session.revoked',
            null,
            ['step' => $session->step->value, 'reason' => $reason->value],
        ));

        $activeSessions
            ->map(fn (PhotoSession $session): InspectionStep => $session->step)
            ->unique()
            ->each(fn (InspectionStep $step) => PhotoSessionChanged::dispatch($reservation->id, $step, false));
    }
}
