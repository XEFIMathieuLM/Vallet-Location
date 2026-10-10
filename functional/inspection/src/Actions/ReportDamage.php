<?php

namespace Functional\Inspection\Actions;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Completeness\ViewCompleteness;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Events\DamageChanged;
use Functional\Inspection\Exceptions\DamageNotReportableException;
use Functional\Inspection\History\InspectionHistory;
use Functional\Inspection\History\InspectionHistoryEvent;
use Functional\Inspection\Models\Damage;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Support\Facades\DB;

class ReportDamage
{
    public function __construct(
        private readonly ViewCompleteness $viewCompleteness,
        private readonly CountUnresolvedDamages $countUnresolvedDamages,
        private readonly InspectionHistory $inspectionHistory,
    ) {}

    public function handle(Reservation $reservation, int $reservationViewId, string $comment, User $reporter): Damage
    {
        $comment = trim($comment);

        if ($comment === '') {
            throw DamageNotReportableException::emptyComment();
        }

        if (! $this->viewCompleteness->for($reservation)->isCompleteFor(InspectionStep::Return)) {
            throw DamageNotReportableException::returnPhotosIncomplete();
        }

        $view = ReservationView::query()->whereBelongsTo($reservation)->findOrFail($reservationViewId);

        $damage = DB::transaction(function () use ($reservation, $view, $comment, $reporter): Damage {
            $damage = Damage::query()->create([
                'reservation_id' => $reservation->id,
                'reservation_view_id' => $view->id,
                'comment' => $comment,
                'reported_by' => $reporter->id,
                'reported_at' => CarbonImmutable::now(),
            ]);

            $this->inspectionHistory->record($reservation, InspectionHistoryEvent::DamageReported, $reporter, [
                'damage_id' => $damage->id,
                'view' => $view->label,
                'comment' => $damage->comment,
            ]);

            return $damage;
        });

        DamageChanged::dispatch($reservation->id, $this->countUnresolvedDamages->for($reservation));

        return $damage;
    }
}
