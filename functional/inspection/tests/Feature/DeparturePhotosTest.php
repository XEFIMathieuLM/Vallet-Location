<?php

namespace Functional\Inspection\Tests\Feature;

use App\Models\User;
use Functional\Booking\Actions\CancelReservation;
use Functional\Booking\Actions\DepartReservation;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Events\ReservationChanged;
use Functional\Booking\Livewire\ReservationDetail;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Inspection\Actions\StorePhoto;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Enums\RevocationReason;
use Functional\Inspection\Exceptions\MissingPhotosException;
use Functional\Inspection\Exceptions\PhotoSessionUnavailableException;
use Functional\Inspection\Livewire\PhotosPanel;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\PhotoSession;
use Functional\Inspection\Models\ReservationView;
use Functional\Inspection\Tests\Concerns\BuildsPhotoSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeparturePhotosTest extends TestCase
{
    use AssertsRefusals, BuildsPhotoSessions, RefreshDatabase;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPhotoStorage();
        $this->employee = $this->employee();
        $this->actingAs($this->employee);
    }

    public function test_the_reservation_detail_shows_the_photos_panel_with_every_view_missing(): void
    {
        $reservation = $this->reservationStartingToday();

        $this->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertSeeLivewire(PhotosPanel::class)
            ->assertSee('Lancer le QR code de départ');
    }

    public function test_a_departure_without_any_qr_code_is_refused_listing_every_view_without_freezing_them(): void
    {
        $reservation = $this->reservationStartingToday();

        $this->assertRefused(
            MissingPhotosException::class,
            'Photos manquantes : Avant, Arrière, Gauche, Droite, Compteur d\'heures',
            fn () => app(DepartReservation::class)->handle($reservation),
        );

        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()?->status);
        $this->assertSame(MachineStatus::Available, $reservation->machine->fresh()?->status);
        $this->assertSame(0, ReservationView::query()->where('reservation_id', $reservation->id)->count());
    }

    public function test_a_departure_with_a_missing_view_is_refused_on_the_detail_screen(): void
    {
        $reservation = $this->reservationStartingToday();
        $this->openSession($reservation);
        ReservationView::query()->where('reservation_id', $reservation->id)->where('label', '!=', 'Droite')->get()
            ->each(fn (ReservationView $view) => Photo::factory()->forView($view, InspectionStep::Departure)->create());

        Livewire::test(ReservationDetail::class, ['reservation' => $reservation])
            ->call('depart')
            ->assertHasErrors('refusal')
            ->assertSee('Photos manquantes : Droite');

        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()?->status);
        $this->assertSame(MachineStatus::Available, $reservation->machine->fresh()?->status);
    }

    public function test_a_complete_departure_is_accepted_then_its_photos_are_frozen(): void
    {
        $reservation = $this->reservationStartingToday();
        $token = $this->openSession($reservation);
        $this->photographEveryView($reservation, InspectionStep::Departure);
        $photo = Photo::query()->firstOrFail();

        app(DepartReservation::class)->handle($reservation);

        $this->assertSame(ReservationStatus::InProgress, $reservation->fresh()?->status);
        $this->assertRefused(
            PhotoSessionUnavailableException::class,
            'Ce lien n\'est plus valable',
            fn () => app(StorePhoto::class)->handle($token, $photo->reservation_view_id, $this->jpeg()),
        );
        Livewire::test(PhotosPanel::class, ['reservation' => $reservation->fresh()])
            ->call('deletePhoto', $photo->id)
            ->assertHasErrors('refusal');
        $this->assertModelExists($photo);
    }

    public function test_the_departure_revokes_the_departure_qr_code(): void
    {
        $reservation = $this->reservationStartingToday();
        $this->openSession($reservation);
        $this->photographEveryView($reservation, InspectionStep::Departure);

        app(DepartReservation::class)->handle($reservation);

        $session = PhotoSession::query()->whereNotNull('revoked_at')->where('step', InspectionStep::Departure)->latest('id')->first();
        $this->assertNotNull($session);
        $this->assertSame(RevocationReason::StepValidated, $session->revoked_reason);
    }

    public function test_cancelling_the_reservation_revokes_its_qr_codes(): void
    {
        $reservation = $this->reservationStartingToday();
        $token = $this->openSession($reservation);

        app(CancelReservation::class)->handle($reservation);

        $session = PhotoSession::query()->where('token_hash', PhotoSession::hashToken($token))->firstOrFail();
        $this->assertSame(RevocationReason::ReservationCancelled, $session->revoked_reason);
    }

    public function test_a_reservation_event_that_changes_nothing_revokes_nothing(): void
    {
        $reservation = $this->reservationStartingToday();
        $token = $this->openSession($reservation);

        ReservationChanged::dispatch($reservation);

        $this->assertNull(PhotoSession::query()->where('token_hash', PhotoSession::hashToken($token))->firstOrFail()->revoked_at);
    }
}
