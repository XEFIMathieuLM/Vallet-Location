<?php

namespace Functional\Inspection\Tests\Feature;

use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Exceptions\MissingPhotosException;
use Functional\Inspection\Guards\PhotosCompleteGuard;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\ReservationView;
use Functional\Inspection\Tests\Concerns\BuildsPhotoSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhotosCompleteGuardTest extends TestCase
{
    use BuildsPhotoSessions, RefreshDatabase;

    public function test_a_departure_without_any_qr_code_is_refused_listing_every_default_view(): void
    {
        $reservation = $this->reservationStartingToday();

        $this->assertThrows(
            fn () => app(PhotosCompleteGuard::class)->beforeDeparture($reservation),
            MissingPhotosException::class,
            'Photos manquantes : Avant, Arrière, Gauche, Droite, Compteur d\'heures',
        );
    }

    public function test_a_departure_with_one_missing_view_lists_only_that_view(): void
    {
        $reservation = $this->reservationStartingToday();
        $this->openSession($reservation);
        ReservationView::query()->where('reservation_id', $reservation->id)->where('label', '!=', 'Droite')->get()
            ->each(fn (ReservationView $view) => Photo::factory()->forView($view, InspectionStep::Departure)->create());

        $this->expectException(MissingPhotosException::class);
        $this->expectExceptionMessage('Photos manquantes : Droite');

        app(PhotosCompleteGuard::class)->beforeDeparture($reservation);
    }

    public function test_a_departure_with_every_view_photographed_is_accepted(): void
    {
        $reservation = $this->reservationStartingToday();
        $this->openSession($reservation);
        ReservationView::query()->where('reservation_id', $reservation->id)->get()
            ->each(fn (ReservationView $view) => Photo::factory()->forView($view, InspectionStep::Departure)->create());

        app(PhotosCompleteGuard::class)->beforeDeparture($reservation);

        $this->expectException(MissingPhotosException::class);
        app(PhotosCompleteGuard::class)->beforeReturn($reservation);
    }
}
