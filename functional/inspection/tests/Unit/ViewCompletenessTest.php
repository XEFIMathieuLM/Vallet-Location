<?php

namespace Functional\Inspection\Tests\Unit;

use Functional\Booking\Models\Reservation;
use Functional\Inspection\Actions\ViewCompleteness;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewCompletenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_views_are_counted_per_step(): void
    {
        $reservation = Reservation::factory()->create();
        $front = ReservationView::factory()->for($reservation)->create(['position' => 1]);
        $back = ReservationView::factory()->for($reservation)->create(['position' => 2]);
        ReservationView::factory()->for($reservation)->create(['position' => 3]);
        Photo::factory()->count(2)->forView($front, InspectionStep::Departure)->create();
        Photo::factory()->forView($back, InspectionStep::Departure)->create();
        Photo::factory()->forView($back, InspectionStep::Return)->create();

        $completeness = app(ViewCompleteness::class)->for($reservation);

        $this->assertSame(3, $completeness->viewsCount);
        $this->assertSame(1, $completeness->missingCount(InspectionStep::Departure));
        $this->assertSame(2, $completeness->missingCount(InspectionStep::Return));
    }

    public function test_photos_of_another_reservation_are_ignored(): void
    {
        $reservation = Reservation::factory()->create();
        ReservationView::factory()->for($reservation)->create(['position' => 1]);
        Photo::factory()->forView(ReservationView::factory()->create(['position' => 1]), InspectionStep::Departure)->create();

        $this->assertSame(1, app(ViewCompleteness::class)->for($reservation)->missingCount(InspectionStep::Departure));
    }
}
