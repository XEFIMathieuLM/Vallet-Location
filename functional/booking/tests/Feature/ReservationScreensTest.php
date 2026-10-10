<?php

namespace Functional\Booking\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ConflictReason;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Livewire\ReservationList;
use Functional\Booking\Models\Reservation;
use Functional\Booking\Tests\Concerns\WithoutTransitionExtensions;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReservationScreensTest extends TestCase
{
    use CreatesUsers, RefreshDatabase, WithoutTransitionExtensions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
        $this->travelTo(CarbonImmutable::parse('2026-11-10 09:00'));
    }

    public function test_the_list_filters_reservations_in_conflict_and_shows_the_reason(): void
    {
        $this->reservation('NAC-0001', ReservationStatus::Confirmed, ConflictReason::VgpExpired);
        $this->reservation('NAC-0002', ReservationStatus::Confirmed, null);

        Livewire::actingAs($this->employee())
            ->test(ReservationList::class)
            ->assertSee('NAC-0001')
            ->assertSee('NAC-0002')
            ->set('isInConflict', true)
            ->assertSee('NAC-0001')
            ->assertSee(ConflictReason::VgpExpired->label())
            ->assertDontSee('NAC-0002');
    }

    public function test_the_list_filters_by_status(): void
    {
        $this->reservation('NAC-0001', ReservationStatus::InProgress, null);
        $this->reservation('NAC-0002', ReservationStatus::Cancelled, null);

        Livewire::actingAs($this->employee())
            ->test(ReservationList::class)
            ->set('status', ReservationStatus::InProgress->value)
            ->assertSee('NAC-0001')
            ->assertDontSee('NAC-0002');
    }

    public function test_the_detail_screen_shows_the_reservation_and_its_actions(): void
    {
        $reservation = $this->reservation('NAC-0001', ReservationStatus::Confirmed, null);

        $this->actingAs($this->employee())
            ->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertSee('NAC-0001')
            ->assertSee($reservation->customer->name)
            ->assertSee(__('booking::reservations.transitions.departure'))
            ->assertSee(__('booking::reservations.transitions.cancellation'));
    }

    public function test_the_reservation_screens_require_the_permission(): void
    {
        $reservation = $this->reservation('NAC-0001', ReservationStatus::Confirmed, null);
        $this->actingAs($this->userWithoutPermission());

        $this->get(route('reservations.index'))->assertForbidden();
        $this->get(route('reservations.show', $reservation))->assertForbidden();
    }

    private function reservation(string $reference, ReservationStatus $status, ?ConflictReason $conflictReason): Reservation
    {
        return Reservation::factory()
            ->for(Machine::factory()->state(['reference' => $reference]))
            ->between(CarbonImmutable::parse('2026-11-10'), CarbonImmutable::parse('2026-11-14'))
            ->withStatus($status)
            ->create(['conflict_reason' => $conflictReason]);
    }
}
