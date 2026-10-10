<?php

namespace Functional\Inspection\Tests\Feature;

use Closure;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Functional\Inspection\Actions\CategoryViews\AddCategoryView;
use Functional\Inspection\Actions\CategoryViews\RemoveCategoryView;
use Functional\Inspection\Actions\FreezeReservationViews;
use Functional\Inspection\Actions\RevokePhotoSessions;
use Functional\Inspection\Completeness\ViewCompleteness;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Enums\RevocationReason;
use Functional\Inspection\Models\CategoryView;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\PhotoSession;
use Functional\Inspection\Models\ReservationView;
use Functional\Inspection\Tests\Concerns\BuildsPhotoSessions;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QueryCountTest extends TestCase
{
    use BuildsPhotoSessions, RefreshDatabase;

    public function test_freezing_the_views_inserts_them_in_one_query(): void
    {
        $category = MachineCategory::factory()->create();
        CategoryView::factory()->count(8)->sequence(fn ($sequence): array => ['position' => $sequence->index + 1])->for($category, 'category')->create();
        $reservation = Reservation::factory()->for(Machine::factory()->for($category, 'category'))->create();

        $inserts = $this->countQueries('insert into "reservation_views"', fn () => app(FreezeReservationViews::class)->handle($reservation));

        $this->assertSame(1, $inserts);
        $this->assertSame(8, ReservationView::query()->where('reservation_id', $reservation->id)->count());
    }

    public function test_the_first_change_on_a_default_category_copies_the_default_views_in_one_query(): void
    {
        $category = MachineCategory::factory()->create();

        $inserts = $this->countQueries('insert into "category_views"', fn () => app(AddCategoryView::class)->handle($category, 'Godet'));

        $this->assertSame(2, $inserts);
        $this->assertSame(6, CategoryView::query()->where('machine_category_id', $category->id)->count());
    }

    public function test_removing_a_view_renumbers_the_next_ones_in_one_query(): void
    {
        $category = MachineCategory::factory()->create();
        CategoryView::factory()->count(8)->sequence(fn ($sequence): array => ['position' => $sequence->index + 1])->for($category, 'category')->create();

        $updates = $this->countQueries('update "category_views"', fn () => app(RemoveCategoryView::class)->handle($category, 1));

        $this->assertSame(1, $updates);
        $this->assertSame(range(1, 7), CategoryView::query()->where('machine_category_id', $category->id)->orderBy('position')->pluck('position')->all());
    }

    public function test_revoking_several_sessions_writes_one_history_entry(): void
    {
        $reservation = Reservation::factory()->create();
        PhotoSession::factory()->for($reservation)->forStep(InspectionStep::Departure)->create();
        PhotoSession::factory()->for($reservation)->forStep(InspectionStep::Return)->create();

        $inserts = $this->countQueries('insert into "activity_log"', fn () => app(RevokePhotoSessions::class)->handle($reservation, InspectionStep::cases(), RevocationReason::ReservationCancelled));

        $this->assertSame(1, $inserts);
        $this->assertSame(2, PhotoSession::query()->whereNotNull('revoked_at')->count());
    }

    public function test_the_completeness_of_both_steps_is_computed_in_one_query(): void
    {
        $reservation = Reservation::factory()->create();
        $front = ReservationView::factory()->for($reservation)->create(['position' => 1]);
        ReservationView::factory()->for($reservation)->create(['position' => 2]);
        Photo::factory()->forView($front, InspectionStep::Departure)->create();

        $completeness = null;
        $queries = $this->countQueries('', function () use ($reservation, &$completeness): void {
            $completeness = app(ViewCompleteness::class)->for($reservation);
        });

        $this->assertSame(1, $queries);
        $this->assertSame(1, $completeness?->missingCount(InspectionStep::Departure));
        $this->assertSame(2, $completeness?->missingCount(InspectionStep::Return));
        $this->assertFalse($completeness?->isCompleteFor(InspectionStep::Departure));
    }

    private function countQueries(string $sqlPrefix, Closure $action): int
    {
        $matchingQueries = 0;
        DB::listen(function (QueryExecuted $query) use ($sqlPrefix, &$matchingQueries): void {
            if (str_starts_with($query->sql, $sqlPrefix)) {
                $matchingQueries++;
            }
        });

        $action();

        return $matchingQueries;
    }
}
