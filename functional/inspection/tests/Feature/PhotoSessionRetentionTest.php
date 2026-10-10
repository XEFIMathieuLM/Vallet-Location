<?php

namespace Functional\Inspection\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Enums\RevocationReason;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\PhotoSession;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhotoSessionRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-10-10 03:00:00');
    }

    public function test_sessions_expired_or_revoked_more_than_thirty_days_ago_without_photo_are_deleted(): void
    {
        $longExpired = PhotoSession::factory()->create(['expires_at' => '2026-09-01 10:00:00']);
        $longRevoked = PhotoSession::factory()->create(['expires_at' => '2026-12-01 10:00:00', 'revoked_at' => '2026-09-01 10:00:00', 'revoked_reason' => RevocationReason::Replaced]);
        $recentlyExpired = PhotoSession::factory()->create(['expires_at' => '2026-10-01 10:00:00']);
        $active = PhotoSession::factory()->create(['expires_at' => '2026-10-10 03:20:00']);

        $this->prune();

        $this->assertModelMissing($longExpired);
        $this->assertModelMissing($longRevoked);
        $this->assertModelExists($recentlyExpired);
        $this->assertModelExists($active);
    }

    public function test_a_session_that_carries_photos_is_kept(): void
    {
        $reservation = Reservation::factory()->create();
        $session = PhotoSession::factory()->for($reservation)->create(['expires_at' => '2026-08-01 10:00:00']);
        Photo::factory()->forView(ReservationView::factory()->for($reservation)->create(), InspectionStep::Departure)->create(['photo_session_id' => $session->id]);

        $this->prune();

        $this->assertModelExists($session);
    }

    public function test_the_session_retention_is_configurable(): void
    {
        config(['inspection.photo_session_retention_days' => 5]);
        $expiredLastWeek = PhotoSession::factory()->create(['expires_at' => '2026-10-03 10:00:00']);

        $this->prune();

        $this->assertModelMissing($expiredLastWeek);
    }

    public function test_the_photo_retention_is_configurable(): void
    {
        config(['inspection.photo_retention_days' => 30]);
        $reservation = Reservation::factory()->withStatus(ReservationStatus::Closed)->create(['returned_at' => '2026-08-01']);
        $photo = Photo::factory()->forView(ReservationView::factory()->for($reservation)->create(), InspectionStep::Return)->create();

        $this->assertContains($photo->id, (new Photo)->prunable()->pluck('id')->all());
    }

    public function test_the_application_wide_prune_covers_photos_and_sessions(): void
    {
        $this->assertContains(Photo::class, config()->array('prunable.models'));
        $this->assertContains(PhotoSession::class, config()->array('prunable.models'));

        $pruneCommand = collect(app(Schedule::class)->events())
            ->map(fn (ScheduledEvent $event): string => (string) $event->command)
            ->sole(fn (string $command): bool => str_contains($command, 'model:prune'));

        $this->assertStringContainsString("--model='".Photo::class."'", $pruneCommand);
        $this->assertStringContainsString("--model='".PhotoSession::class."'", $pruneCommand);
    }

    private function prune(): void
    {
        $this->artisan('model:prune', ['--model' => [PhotoSession::class]])->assertSuccessful();
    }
}
