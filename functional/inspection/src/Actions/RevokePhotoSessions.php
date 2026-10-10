<?php

namespace Functional\Inspection\Actions;

use Carbon\CarbonImmutable;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Enums\RevocationReason;
use Functional\Inspection\Events\PhotoSessionChanged;
use Functional\Inspection\Models\PhotoSession;
use Functional\Inspection\Support\InspectionHistory;
use Illuminate\Database\Eloquent\Builder;
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
            $activeSessions = fn (): Builder => PhotoSession::query()
                ->where('reservation_id', $reservation->id)
                ->whereIn('step', $steps)
                ->whereNull('revoked_at')
                ->where('expires_at', '>', $now);

            $sessionSteps = $activeSessions()->distinct()->orderBy('step')->pluck('step');

            if ($sessionSteps->isEmpty()) {
                return collect();
            }

            $revokedSessionsCount = $activeSessions()->update(['revoked_at' => $now, 'revoked_reason' => $reason]);

            $this->inspectionHistory->record($reservation, 'photo_session.revoked', null, [
                'steps' => $sessionSteps->map(fn (InspectionStep $step): string => $step->value)->implode(','),
                'sessions_count' => $revokedSessionsCount,
                'reason' => $reason->value,
            ]);

            return $sessionSteps;
        });

        $revokedSteps->each(fn (InspectionStep $step) => PhotoSessionChanged::dispatch($reservation->id, $step, false));
    }
}
