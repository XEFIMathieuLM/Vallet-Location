<?php

namespace Functional\Inspection\Tests\Feature;

use Functional\Booking\Enums\ReservationTransition;
use Functional\Booking\Extensions\ReservationDetailSections;
use Functional\Booking\Livewire\ReservationDetail;
use Functional\Inspection\Livewire\PhotosPanel;
use Functional\Inspection\Tests\Concerns\BuildsPhotoSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PhotosPanelReadinessTest extends TestCase
{
    use BuildsPhotoSessions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->seededEmployee());
    }

    public function test_the_departure_button_is_disabled_on_first_display_until_the_panel_reports_ready(): void
    {
        $reservation = $this->reservationStartingToday();

        $detail = Livewire::test(ReservationDetail::class, ['reservation' => $reservation]);

        $this->assertFalse($detail->instance()->isReadyFor(ReservationTransition::Departure));
        $this->assertFalse($detail->instance()->isReadyFor(ReservationTransition::Return));

        $detail->dispatch(PhotosPanel::READINESS_EVENT, step: 'departure', section: 'another-section', is_ready: true);
        $this->assertFalse($detail->instance()->isReadyFor(ReservationTransition::Departure));

        $detail->dispatch(PhotosPanel::READINESS_EVENT, step: 'departure', section: PhotosPanel::SECTION, is_ready: true);
        $this->assertTrue($detail->instance()->isReadyFor(ReservationTransition::Departure));
        $this->assertFalse($detail->instance()->isReadyFor(ReservationTransition::Return));
    }

    public function test_the_photos_panel_guards_the_departure_and_the_return(): void
    {
        $sections = app(ReservationDetailSections::class);

        $this->assertTrue($sections->isGuardedBy(PhotosPanel::SECTION, ReservationTransition::Departure));
        $this->assertTrue($sections->isGuardedBy(PhotosPanel::SECTION, ReservationTransition::Return));
        $this->assertFalse($sections->isGuardedBy(PhotosPanel::SECTION, ReservationTransition::Cancellation));
    }
}
