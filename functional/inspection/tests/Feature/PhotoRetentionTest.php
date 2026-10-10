<?php

namespace Functional\Inspection\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Models\Damage;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('photos');
        CarbonImmutable::setTestNow('2026-10-10 03:00:00');
    }

    public function test_photos_of_a_reservation_closed_more_than_a_year_ago_are_deleted_with_their_files(): void
    {
        $photo = $this->photoOf($this->closedReservation('2025-09-01'));
        $path = $photo->getFirstMedia(Photo::COLLECTION)?->getPathRelativeToRoot();

        $this->prune();

        $this->assertModelMissing($photo);
        Storage::disk('photos')->assertMissing((string) $path);
    }

    public function test_photos_of_a_reservation_closed_less_than_a_year_ago_are_kept(): void
    {
        $photo = $this->photoOf($this->closedReservation('2025-11-01'));

        $this->prune();

        $this->assertModelExists($photo);
    }

    public function test_photos_with_an_unresolved_damage_are_kept(): void
    {
        $reservation = $this->closedReservation('2025-09-01');
        $photo = $this->photoOf($reservation);
        Damage::factory()->for($reservation)->create(['reservation_view_id' => $photo->reservation_view_id]);

        $this->prune();

        $this->assertModelExists($photo);
    }

    public function test_photos_are_kept_one_year_after_the_last_damage_was_resolved(): void
    {
        $recentlyResolved = $this->closedReservation('2025-01-01');
        $recentPhoto = $this->photoOf($recentlyResolved);
        Damage::factory()->for($recentlyResolved)->resolved()->create(['reservation_view_id' => $recentPhoto->reservation_view_id, 'resolved_at' => '2026-04-01']);
        $longResolved = $this->closedReservation('2025-01-01');
        $oldPhoto = $this->photoOf($longResolved);
        Damage::factory()->for($longResolved)->resolved()->create(['reservation_view_id' => $oldPhoto->reservation_view_id, 'resolved_at' => '2025-09-01']);

        $this->prune();

        $this->assertModelExists($recentPhoto);
        $this->assertModelMissing($oldPhoto);
    }

    public function test_photos_of_a_cancelled_reservation_are_deleted_a_year_after_reception(): void
    {
        $reservation = Reservation::factory()->withStatus(ReservationStatus::Cancelled)->create();
        $oldPhoto = $this->photoOf($reservation, '2025-09-01');
        $recentPhoto = $this->photoOf($reservation, '2025-11-01');

        $this->prune();

        $this->assertModelMissing($oldPhoto);
        $this->assertModelExists($recentPhoto);
    }

    public function test_photos_of_a_reservation_still_in_progress_are_kept(): void
    {
        $photo = $this->photoOf(Reservation::factory()->withStatus(ReservationStatus::InProgress)->create(), '2025-01-01');

        $this->prune();

        $this->assertModelExists($photo);
    }

    public function test_the_prune_runs_every_night(): void
    {
        $pruneEvents = collect(app(Schedule::class)->events())
            ->filter(fn (ScheduledEvent $event): bool => str_contains((string) $event->command, 'model:prune'));

        $this->assertCount(1, $pruneEvents);
        $this->assertSame('15 2 * * *', $pruneEvents->first()?->expression);
    }

    private function closedReservation(string $returnedAt): Reservation
    {
        return Reservation::factory()->withStatus(ReservationStatus::Closed)->create(['returned_at' => $returnedAt]);
    }

    private function photoOf(Reservation $reservation, string $receivedAt = '2025-01-01'): Photo
    {
        $view = ReservationView::factory()->for($reservation)->create();
        $photo = Photo::factory()->forView($view, InspectionStep::Return)->withFile()->create();
        $photo->forceFill(['created_at' => $receivedAt])->saveQuietly();

        return $photo;
    }

    private function prune(): void
    {
        $this->artisan('model:prune', ['--model' => [Photo::class]])->assertSuccessful();
    }
}
