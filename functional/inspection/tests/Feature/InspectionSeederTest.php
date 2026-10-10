<?php

namespace Functional\Inspection\Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Enums\RevocationReason;
use Functional\Inspection\Models\CategoryView;
use Functional\Inspection\Models\Damage;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\PhotoSession;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InspectionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_fresh_seed_shows_every_inspection_state(): void
    {
        Storage::fake('photos');

        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, CategoryView::query()->count());
        $this->assertGreaterThan(0, ReservationView::query()->count());
        foreach (InspectionStep::cases() as $step) {
            $this->assertTrue(Photo::query()->where('step', $step)->whereHas('media')->exists(), "No {$step->value} photo with a file");
        }
        $this->assertTrue(Damage::query()->whereNull('resolved_at')->exists());
        $this->assertTrue(Damage::query()->whereNotNull('resolved_at')->exists());
        $this->assertTrue(PhotoSession::query()->whereNull('revoked_at')->where('expires_at', '>', now())->exists());
        $this->assertTrue(PhotoSession::query()->whereNull('revoked_at')->where('expires_at', '<=', now())->exists());
        foreach (RevocationReason::cases() as $reason) {
            $this->assertTrue(PhotoSession::query()->where('revoked_reason', $reason)->exists(), "No session revoked as {$reason->value}");
        }
        $this->assertSame(0, Photo::query()->whereDoesntHave('session')->count());
        $this->assertSame(0, Damage::query()->whereHas('view', fn (Builder $views): Builder => $views->whereColumn('reservation_views.reservation_id', '!=', 'damages.reservation_id'))->count());
        foreach ([ReservationStatus::InProgress, ReservationStatus::Closed, ReservationStatus::Cancelled] as $status) {
            $this->assertTrue(ReservationView::query()->whereHas('reservation', fn (Builder $reservations): Builder => $reservations->where('status', $status))->exists(), "No inspected {$status->value} reservation");
        }
    }
}
