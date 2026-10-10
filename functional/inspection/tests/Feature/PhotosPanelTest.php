<?php

namespace Functional\Inspection\Tests\Feature;

use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Inspection\Actions\DeletePhoto;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Exceptions\StepAlreadyValidatedException;
use Functional\Inspection\Livewire\PhotosPanel;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\PhotoSession;
use Functional\Inspection\Tests\Concerns\BuildsPhotoSessions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PhotosPanelTest extends TestCase
{
    use AssertsRefusals, BuildsPhotoSessions, RefreshDatabase;

    private Model&Authenticatable&AgencyMember $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPhotoStorage();
        $this->employee = $this->seededEmployee();
        $this->actingAs($this->employee);
    }

    public function test_launching_the_qr_code_shows_it_with_every_view_missing(): void
    {
        $reservation = $this->reservationStartingToday();

        $panel = Livewire::test(PhotosPanel::class, ['reservation' => $reservation])
            ->assertSee('Lancer le QR code de départ')
            ->call('generate')
            ->assertSee('<svg', false)
            ->assertSee('Régénérer le QR code')
            ->assertSee('Compteur d&#039;heures', false)
            ->assertSee('Manquante');

        $session = PhotoSession::query()->sole();
        $this->assertSame($this->employee->id, $session->created_by);
        $this->assertSame($session->token_hash, PhotoSession::hashToken((string) $panel->get('token')));
    }

    public function test_the_departure_button_is_announced_as_not_ready_on_first_display(): void
    {
        $reservation = $this->reservationStartingToday();

        Livewire::test(PhotosPanel::class, ['reservation' => $reservation])
            ->assertDispatched(PhotosPanel::READINESS_EVENT, step: 'departure', section: PhotosPanel::SECTION, is_ready: false)
            ->assertDispatched(PhotosPanel::READINESS_EVENT, step: 'return', section: PhotosPanel::SECTION, is_ready: false);
    }

    public function test_the_departure_is_announced_as_ready_once_every_view_has_a_photo(): void
    {
        $reservation = $this->reservationStartingToday();
        $this->photographEveryView($reservation, InspectionStep::Departure);

        Livewire::test(PhotosPanel::class, ['reservation' => $reservation])
            ->assertDispatched(PhotosPanel::READINESS_EVENT, step: 'departure', section: PhotosPanel::SECTION, is_ready: true)
            ->call('refreshPhotos')
            ->assertDispatched(PhotosPanel::READINESS_EVENT, step: 'departure', section: PhotosPanel::SECTION, is_ready: true);
    }

    public function test_the_qr_code_cannot_be_launched_before_the_start_date(): void
    {
        $reservation = Reservation::factory()->between(today()->addDay()->toImmutable(), today()->addDays(3)->toImmutable())->create();

        Livewire::test(PhotosPanel::class, ['reservation' => $reservation])
            ->assertDontSee('Lancer le QR code')
            ->call('generate')
            ->assertHasErrors('refusal');

        $this->assertSame(0, PhotoSession::query()->count());
    }

    public function test_a_reservation_in_progress_offers_the_return_qr_code_next_to_the_departure_photos(): void
    {
        $reservation = $this->reservationStartingToday();
        $this->photographEveryView($reservation, InspectionStep::Departure);
        $reservation->update(['status' => ReservationStatus::InProgress]);

        Livewire::test(PhotosPanel::class, ['reservation' => $reservation])
            ->assertSee('Lancer le QR code de retour')
            ->call('generate')
            ->assertHasNoErrors();

        $this->assertSame(1, PhotoSession::query()->where('step', InspectionStep::Return)->whereNull('revoked_at')->count());
    }

    public function test_a_photo_can_be_deleted_from_the_desk_until_its_step_is_validated(): void
    {
        $reservation = $this->reservationStartingToday();
        $this->photographEveryView($reservation, InspectionStep::Departure);
        $photo = Photo::query()->firstOrFail();

        Livewire::test(PhotosPanel::class, ['reservation' => $reservation])->call('deletePhoto', $photo->id);
        $this->assertModelMissing($photo);

        $reservation->update(['status' => ReservationStatus::InProgress]);
        $remainingPhoto = Photo::query()->firstOrFail();

        Livewire::test(PhotosPanel::class, ['reservation' => $reservation])
            ->call('deletePhoto', $remainingPhoto->id)
            ->assertHasErrors('refusal');
        $this->assertModelExists($remainingPhoto);
        $this->assertRefused(StepAlreadyValidatedException::class, 'Les photos de départ sont déjà validées', fn () => app(DeletePhoto::class)->handle($remainingPhoto, null));
    }

    public function test_the_desk_cannot_delete_a_photo_of_another_reservation(): void
    {
        $reservation = $this->reservationStartingToday();
        $foreignPhoto = Photo::factory()->create();

        Livewire::test(PhotosPanel::class, ['reservation' => $reservation])
            ->call('deletePhoto', $foreignPhoto->id)
            ->assertNotFound();

        $this->assertModelExists($foreignPhoto);
    }

    public function test_photo_files_are_served_to_employees_only(): void
    {
        $photo = Photo::factory()->withFile()->create();

        $this->get(route('inspection.photo-file', [$photo, 'thumb']))->assertOk();
        $this->get(route('inspection.photo-file', [$photo, 'display']))->assertOk();
        $this->get(route('inspection.photo-file', [$photo, 'original']))->assertNotFound();

        $this->actingAs($this->userWithoutPermission());
        $this->get(route('inspection.photo-file', [$photo, 'thumb']))->assertForbidden();
    }
}
