<?php

namespace Functional\Inspection\Tests\Feature;

use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Livewire\PhotosPanel;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Tests\Concerns\BuildsPhotoSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PhotosPanelRealtimeTest extends TestCase
{
    use BuildsPhotoSessions, RefreshDatabase;

    public function test_the_panel_listens_to_the_photos_and_sessions_of_its_reservation(): void
    {
        $this->actingAs($this->seededEmployee());
        $reservation = $this->reservationStartingToday();

        $listeners = Livewire::test(PhotosPanel::class, ['reservation' => $reservation])->instance()->getListeners();

        $this->assertSame('refreshPhotos', $listeners["echo-private:reservation.{$reservation->id},.photo.changed"]);
        $this->assertSame('refreshSession', $listeners["echo-private:reservation.{$reservation->id},.photo-session.changed"]);
    }

    public function test_a_received_photo_shows_on_the_panel_and_unlocks_the_departure(): void
    {
        $this->setUpPhotoStorage();
        $this->actingAs($this->seededEmployee());
        $reservation = $this->reservationStartingToday();
        $this->openSession($reservation);
        $panel = Livewire::test(PhotosPanel::class, ['reservation' => $reservation])
            ->assertDontSee('photo-fichiers', false);

        $this->photographEveryView($reservation, InspectionStep::Departure);
        $panel->dispatch("echo-private:reservation.{$reservation->id},.photo.changed")
            ->assertSee(route('inspection.photo-file', [Photo::query()->firstOrFail(), 'thumb']), false)
            ->assertDispatched(PhotosPanel::READINESS_EVENT, step: 'departure', section: PhotosPanel::SECTION, is_ready: true);
    }
}
