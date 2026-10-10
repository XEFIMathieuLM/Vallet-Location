<?php

namespace Functional\Inspection\Actions;

use Carbon\CarbonImmutable;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Enums\RevocationReason;
use Functional\Inspection\Events\PhotoSessionChanged;
use Functional\Inspection\Models\PhotoSession;
use Functional\Inspection\Support\InspectionHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RevokePhotoSessions
{
    public function __construct(private readonly InspectionHistory $inspectionHistory) {}

    /**
     * @param  list<InspectionStep>  $steps
     */
    public function handle(Reservation $reservation, array $steps, RevocationReason $reason): void
    {
        $now = CarbonImmutable::now();

        $revokedSteps = DB::transaction(function () use ($reservation, $steps, $reason, $now): Collection {
            $activeSessions = PhotoSession::query()
                ->where('reservation_id', $reservation->id)
                ->whereIn('step', $steps)
                ->whereNull('revoked_at')
                ->where('expires_at', '>', $now)
                ->get(['id', 'step']);

            if ($activeSessions->isEmpty()) {
                return collect();
            }

            PhotoSession::query()
                ->whereKey($activeSessions->modelKeys())
                ->update(['revoked_at' => $now, 'revoked_reason' => $reason]);

            $sessionSteps = $activeSessions->map(fn (PhotoSession $session): InspectionStep => $session->step)->unique()->values();

            $this->inspectionHistory->record($reservation, 'photo_session.revoked', null, [
                'steps' => $sessionSteps->map(fn (InspectionStep $step): string => $step->value)->implode(','),
                'sessions_count' => $activeSessions->count(),
                'reason' => $reason->value,
            ]);

            return $sessionSteps;
        });

        $revokedSteps->each(fn (InspectionStep $step) => PhotoSessionChanged::dispatch($reservation->id, $step, false));
    }
}
