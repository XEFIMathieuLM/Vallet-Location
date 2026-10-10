<?php

namespace Functional\Inspection\Actions;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Enums\RevocationReason;
use Functional\Inspection\Events\PhotoSessionChanged;
use Functional\Inspection\Exceptions\StepNotOpenException;
use Functional\Inspection\Models\PhotoSession;
use Functional\Inspection\Support\InspectionHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OpenPhotoSession
{
    public function __construct(
        private readonly FreezeReservationViews $freezeReservationViews,
        private readonly RevokePhotoSessions $revokePhotoSessions,
        private readonly InspectionHistory $inspectionHistory,
    ) {}

    public function handle(Reservation $reservation, InspectionStep $step, User $author): string
    {
        if (! $step->isOpenFor($reservation)) {
            throw StepNotOpenException::for($step);
        }

        $this->freezeReservationViews->handle($reservation);

        $token = Str::random(40);

        DB::transaction(function () use ($reservation, $step, $author, $token): void {
            Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            $this->revokePhotoSessions->handle($reservation, [$step], RevocationReason::Replaced);

            PhotoSession::query()->create([
                'reservation_id' => $reservation->id,
                'step' => $step,
                'token_hash' => PhotoSession::hashToken($token),
                'created_by' => $author->id,
                'expires_at' => CarbonImmutable::now()->addMinutes(config()->integer('inspection.session_lifetime_minutes')),
            ]);

            $this->inspectionHistory->record($reservation, 'photo_session.opened', $author, ['step' => $step->value]);
        });

        PhotoSessionChanged::dispatch($reservation->id, $step, true);

        return $token;
    }
}
