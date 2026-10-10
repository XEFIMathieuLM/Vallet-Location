<?php

namespace Functional\Inspection\Tests\Feature;

use Functional\Booking\Livewire\ReservationDetail;
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

        $this->actingAs($this->employee());
    }

    public function test_the_departure_button_is_disabled_on_first_display_until_the_panel_reports_ready(): void
    {
        $reservation = $this->reservationStartingToday();

        $detail = Livewire::test(ReservationDetail::class, ['reservation' => $reservation]);

        $this->assertFalse($detail->instance()->isReadyFor(ReservationDetail::DEPARTURE_STEP));
        $this->assertFalse($detail->instance()->isReadyFor(ReservationDetail::RETURN_STEP));

        $detail->dispatch('reservation-transition-readiness', step: 'departure', is_ready: true);

        $this->assertTrue($detail->instance()->isReadyFor(ReservationDetail::DEPARTURE_STEP));
    }
}
