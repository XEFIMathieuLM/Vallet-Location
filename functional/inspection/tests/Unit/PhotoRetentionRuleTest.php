<?php

namespace Functional\Inspection\Tests\Unit;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Models\Damage;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhotoRetentionRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_rule_selects_only_the_photos_past_their_retention(): void
    {
        CarbonImmutable::setTestNow('2026-10-10 03:00:00');
        $closedLongAgo = $this->photoOf(ReservationStatus::Closed, '2025-09-01');
        $closedRecently = $this->photoOf(ReservationStatus::Closed, '2025-11-01');
        $withUnresolvedDamage = $this->photoOf(ReservationStatus::Closed, '2025-09-01');
        Damage::factory()->create(['reservation_id' => $withUnresolvedDamage->reservation_id, 'reservation_view_id' => $withUnresolvedDamage->reservation_view_id]);
        $cancelledLongAgo = $this->photoOf(ReservationStatus::Cancelled, null, '2025-09-01');
        $inProgress = $this->photoOf(ReservationStatus::InProgress, null, '2025-01-01');

        $prunableIds = (new Photo)->prunable()->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$closedLongAgo->id, $cancelledLongAgo->id], $prunableIds);
        $this->assertNotContains($closedRecently->id, $prunableIds);
        $this->assertNotContains($inProgress->id, $prunableIds);
    }

    private function photoOf(ReservationStatus $status, ?string $returnedAt, string $receivedAt = '2025-01-01'): Photo
    {
        $reservation = Reservation::factory()->withStatus($status)->create(['returned_at' => $returnedAt]);
        $photo = Photo::factory()->forView(ReservationView::factory()->for($reservation)->create(), InspectionStep::Return)->create();
        $photo->forceFill(['created_at' => $receivedAt])->saveQuietly();

        return $photo;
    }
}
