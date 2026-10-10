<?php

namespace Functional\Booking\Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissionSeeder;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Enums\ReservationTransition;
use Functional\Booking\Extensions\ReservationDetailSections;
use Functional\Booking\Extensions\ReservationTransitionGuards;
use Functional\Booking\Livewire\ReservationDetail;
use Functional\Booking\Models\Reservation;
use Functional\Booking\Tests\Concerns\WithoutTransitionExtensions;
use Functional\Booking\Tests\Doubles\RefusingGuard;
use Functional\Booking\Tests\Doubles\TestReservationSection;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Machine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class ReservationDetailActionsTest extends TestCase
{
    use RefreshDatabase, WithoutTransitionExtensions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-10 09:00'));
    }

    public function test_the_departure_and_return_buttons_drive_the_lifecycle(): void
    {
        $reservation = $this->reservation(ReservationStatus::Confirmed, MachineStatus::Available, '2026-11-10');

        $this->detail($reservation)
            ->call('depart')
            ->assertHasNoErrors()
            ->call('returnMachine', 'workshop')
            ->assertHasNoErrors();

        $this->assertSame(ReservationStatus::Closed, $reservation->fresh()?->status);
        $this->assertSame(MachineStatus::Workshop, $reservation->machine->fresh()?->status);
    }

    public function test_a_departure_before_the_start_date_shows_the_precise_refusal(): void
    {
        $reservation = $this->reservation(ReservationStatus::Confirmed, MachineStatus::Available, '2026-11-12');

        $this->detail($reservation)
            ->call('depart')
            ->assertHasErrors('refusal')
            ->assertSee('12/11/2026');

        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()?->status);
    }

    public function test_cancelling_after_departure_shows_the_refusal(): void
    {
        $reservation = $this->reservation(ReservationStatus::InProgress, MachineStatus::RentedOut, '2026-11-10');

        $this->detail($reservation)
            ->call('cancel')
            ->assertHasErrors('refusal');

        $this->assertSame(ReservationStatus::InProgress, $reservation->fresh()?->status);
    }

    public function test_a_guard_refusal_is_displayed_like_the_other_refusals(): void
    {
        app(ReservationTransitionGuards::class)->register(RefusingGuard::class);
        $reservation = $this->reservation(ReservationStatus::Confirmed, MachineStatus::Available, '2026-11-10');

        $this->detail($reservation)
            ->call('depart')
            ->assertHasErrors('refusal')
            ->assertSee('Photos de départ manquantes.');
    }

    public function test_without_registered_section_the_buttons_are_enabled(): void
    {
        $reservation = $this->reservation(ReservationStatus::Confirmed, MachineStatus::Available, '2026-11-10');

        $this->assertTrue($this->detail($reservation)->instance()->isReadyFor(ReservationTransition::Departure));
    }

    public function test_a_registered_section_is_rendered_and_holds_the_buttons_until_it_is_ready(): void
    {
        Livewire::component('test-reservation-section', TestReservationSection::class);
        app(ReservationDetailSections::class)->register('test-reservation-section', 10);
        $reservation = $this->reservation(ReservationStatus::Confirmed, MachineStatus::Available, '2026-11-10');

        $detail = $this->detail($reservation)->assertSee('Section de test');
        $this->assertFalse($detail->instance()->isReadyFor(ReservationTransition::Departure));

        $detail->dispatch('reservation-transition-readiness', step: ReservationTransition::Departure->value, is_ready: true);
        $this->assertTrue($detail->instance()->isReadyFor(ReservationTransition::Departure));
        $this->assertFalse($detail->instance()->isReadyFor(ReservationTransition::Return));
    }

    private function detail(Reservation $reservation): Testable
    {
        return Livewire::actingAs(User::factory()->employee()->create())
            ->test(ReservationDetail::class, ['reservation' => $reservation]);
    }

    private function reservation(ReservationStatus $status, MachineStatus $machineStatus, string $startDate): Reservation
    {
        return Reservation::factory()
            ->for(Machine::factory()->withStatus($machineStatus))
            ->between(CarbonImmutable::parse($startDate), CarbonImmutable::parse('2026-11-14'))
            ->withStatus($status)
            ->create();
    }
}
