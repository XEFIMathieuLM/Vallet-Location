<?php

namespace Functional\Inspection\Tests\Feature;

use Functional\Booking\Actions\DepartReservation;
use Functional\Booking\Actions\ReturnReservation;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Enums\ReturnCondition;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Inspection\Actions\MissingViews;
use Functional\Inspection\Actions\StorePhoto;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Exceptions\MissingPhotosException;
use Functional\Inspection\Exceptions\PhotoSessionUnavailableException;
use Functional\Inspection\Livewire\PhotosPanel;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\ReservationView;
use Functional\Inspection\Tests\Concerns\BuildsPhotoSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReturnPhotosTest extends TestCase
{
    use AssertsRefusals, BuildsPhotoSessions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPhotoStorage();
        $this->actingAs($this->employee());
    }

    public function test_the_return_qr_code_requires_the_same_views_as_the_departure(): void
    {
        $reservation = $this->departedReservation();
        $departureViewIds = ReservationView::query()->where('reservation_id', $reservation->id)->pluck('id')->all();

        $this->openSession($reservation, InspectionStep::Return);

        $this->assertSame($departureViewIds, app(MissingViews::class)->for($reservation, InspectionStep::Return)->modelKeys());
    }

    public function test_a_return_with_missing_views_is_refused(): void
    {
        $reservation = $this->departedReservation();
        $this->openSession($reservation, InspectionStep::Return);
        Photo::factory()->forView($this->firstView($reservation), InspectionStep::Return)->create();

        $this->assertRefused(
            MissingPhotosException::class,
            'Photos manquantes : Arrière, Gauche, Droite, Compteur d\'heures',
            fn () => app(ReturnReservation::class)->handle($reservation, ReturnCondition::GoodState),
        );

        $this->assertSame(ReservationStatus::InProgress, $reservation->fresh()?->status);
    }

    public function test_a_complete_return_is_accepted_then_its_photos_are_frozen(): void
    {
        $reservation = $this->departedReservation();
        $token = $this->openSession($reservation, InspectionStep::Return);
        $this->photographEveryView($reservation, InspectionStep::Return);
        $returnPhoto = Photo::query()->where('step', InspectionStep::Return)->firstOrFail();

        app(ReturnReservation::class)->handle($reservation, ReturnCondition::GoodState);

        $this->assertSame(ReservationStatus::Closed, $reservation->fresh()?->status);
        $this->assertRefused(
            PhotoSessionUnavailableException::class,
            'Ce lien n\'est plus valable',
            fn () => app(StorePhoto::class)->handle($token, $returnPhoto->reservation_view_id, $this->jpeg()),
        );
        Livewire::test(PhotosPanel::class, ['reservation' => $reservation->fresh()])
            ->call('deletePhoto', $returnPhoto->id)
            ->assertHasErrors('refusal');
        $this->assertModelExists($returnPhoto);
    }

    public function test_the_departure_qr_code_scanned_during_the_rental_is_no_longer_valid(): void
    {
        $reservation = $this->reservationStartingToday();
        $departureToken = $this->openSession($reservation);
        $this->photographEveryView($reservation, InspectionStep::Departure);
        app(DepartReservation::class)->handle($reservation);

        $this->get(route('inspection.phone', $departureToken))
            ->assertOk()
            ->assertSee('Ce lien n&#039;est plus valable', false);
    }

    private function departedReservation(): Reservation
    {
        $reservation = $this->reservationStartingToday();
        $this->openSession($reservation);
        $this->photographEveryView($reservation, InspectionStep::Departure);

        return app(DepartReservation::class)->handle($reservation);
    }
}
