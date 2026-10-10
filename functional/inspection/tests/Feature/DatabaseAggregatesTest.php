<?php

namespace Functional\Inspection\Tests\Feature;

use Closure;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\MachineCategory;
use Functional\Inspection\Actions\CategoryViews\AddCategoryView;
use Functional\Inspection\Actions\CategoryViews\RemoveCategoryView;
use Functional\Inspection\Actions\RevokePhotoSessions;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Enums\RevocationReason;
use Functional\Inspection\Models\CategoryView;
use Functional\Inspection\Models\PhotoSession;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class DatabaseAggregatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_next_position_of_a_new_view_is_computed_by_the_database(): void
    {
        $category = MachineCategory::factory()->create();
        CategoryView::factory()->for($category, 'category')->create(['label' => 'Godet', 'position' => 7]);

        $sql = $this->sqlOf(fn () => app(AddCategoryView::class)->handle($category, 'Bras'));

        $this->assertStringContainsString('max("position")', $sql);
        $this->assertSame(8, CategoryView::query()->where('label', 'Bras')->value('position'));
    }

    public function test_the_last_view_rule_counts_the_views_in_the_database(): void
    {
        $category = MachineCategory::factory()->create();
        CategoryView::factory()->for($category, 'category')->create(['label' => 'Godet', 'position' => 1]);
        CategoryView::factory()->for($category, 'category')->create(['label' => 'Bras', 'position' => 2]);

        $sql = $this->sqlOf(fn () => app(RemoveCategoryView::class)->handle($category, 1));

        $this->assertStringContainsString('select count(*) as "aggregate" from "category_views"', $sql);
        $this->assertSame(['Bras'], CategoryView::query()->where('machine_category_id', $category->id)->pluck('label')->all());
    }

    public function test_the_revocation_history_counts_the_sessions_in_the_database(): void
    {
        $reservation = Reservation::factory()->create();
        PhotoSession::factory()->for($reservation)->forStep(InspectionStep::Departure)->count(2)->create();
        PhotoSession::factory()->for($reservation)->forStep(InspectionStep::Return)->create();

        $sql = $this->sqlOf(fn () => app(RevokePhotoSessions::class)->handle($reservation, InspectionStep::cases(), RevocationReason::ReservationCancelled));

        $this->assertStringContainsString('select distinct "step" from "photo_sessions"', $sql);
        $properties = Activity::query()->where('event', 'photo_session.revoked')->sole()->properties;
        $this->assertSame(3, $properties['sessions_count']);
        $this->assertSame('departure,return', $properties['steps']);
    }

    private function sqlOf(Closure $action): string
    {
        $statements = [];
        DB::listen(function (QueryExecuted $query) use (&$statements): void {
            $statements[] = $query->sql;
        });

        $action();

        return implode("\n", $statements);
    }
}
