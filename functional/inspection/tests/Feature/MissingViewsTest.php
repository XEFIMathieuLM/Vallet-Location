<?php

namespace Functional\Inspection\Tests\Feature;

use Functional\Booking\Models\Reservation;
use Functional\Inspection\Actions\MissingViews;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissingViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_view_is_missing_until_it_has_a_photo_of_the_step(): void
    {
        $reservation = Reservation::factory()->create();
        $front = ReservationView::factory()->for($reservation)->create(['label' => 'Avant', 'position' => 1]);
        $back = ReservationView::factory()->for($reservation)->create(['label' => 'Arrière', 'position' => 2]);
        Photo::factory()->forView($front, InspectionStep::Departure)->create();
        Photo::factory()->forView($back, InspectionStep::Return)->create();

        $missingAtDeparture = app(MissingViews::class)->for($reservation, InspectionStep::Departure);
        $missingAtReturn = app(MissingViews::class)->for($reservation, InspectionStep::Return);

        $this->assertSame([$back->id], $missingAtDeparture->modelKeys());
        $this->assertSame([$front->id], $missingAtReturn->modelKeys());
    }

    public function test_several_photos_on_one_view_count_once(): void
    {
        $reservation = Reservation::factory()->create();
        $front = ReservationView::factory()->for($reservation)->create(['position' => 1]);
        Photo::factory()->count(2)->forView($front, InspectionStep::Departure)->create();

        $this->assertTrue(app(MissingViews::class)->for($reservation, InspectionStep::Departure)->isEmpty());
    }

    public function test_photos_of_another_reservation_do_not_count(): void
    {
        $reservation = Reservation::factory()->create();
        $front = ReservationView::factory()->for($reservation)->create(['position' => 1]);
        $otherView = ReservationView::factory()->create(['position' => 1]);
        Photo::factory()->forView($otherView, InspectionStep::Departure)->create();

        $this->assertSame([$front->id], app(MissingViews::class)->for($reservation, InspectionStep::Departure)->modelKeys());
    }
}
