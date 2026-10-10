<?php

namespace Functional\Inspection\Actions;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Events\DamageChanged;
use Functional\Inspection\Exceptions\DamageNotReportableException;
use Functional\Inspection\Models\Damage;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\ReservationView;
use Functional\Inspection\Support\InspectionHistory;

class ReportDamage
{
    public function __construct(
        private readonly MissingViews $missingViews,
        private readonly CountUnresolvedDamages $countUnresolvedDamages,
        private readonly InspectionHistory $inspectionHistory,
    ) {}

    public function handle(Reservation $reservation, int $reservationViewId, string $comment, User $reporter): Damage
    {
        $comment = trim($comment);

        if ($comment === '') {
            throw DamageNotReportableException::emptyComment();
        }

        $hasReturnPhotos = Photo::query()
            ->where('reservation_id', $reservation->id)
            ->where('step', InspectionStep::Return)
            ->exists();

        if (! $hasReturnPhotos || $this->missingViews->for($reservation, InspectionStep::Return)->isNotEmpty()) {
            throw DamageNotReportableException::returnPhotosIncomplete();
        }

        $view = ReservationView::query()->where('reservation_id', $reservation->id)->findOrFail($reservationViewId);

        $damage = Damage::query()->create([
            'reservation_id' => $reservation->id,
            'reservation_view_id' => $view->id,
            'comment' => $comment,
            'reported_by' => $reporter->id,
            'reported_at' => CarbonImmutable::now(),
        ]);

        $this->inspectionHistory->record($reservation, 'damage.reported', $reporter, [
            'damage_id' => $damage->id,
            'view' => $view->label,
            'comment' => $damage->comment,
        ]);

        DamageChanged::dispatch($reservation->id, $this->countUnresolvedDamages->for($reservation->id));

        return $damage;
    }
}
