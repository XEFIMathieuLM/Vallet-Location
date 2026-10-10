<?php

namespace Functional\Booking\Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissionSeeder;
use Functional\Booking\Actions\CancelReservation;
use Functional\Booking\Actions\CreateReservation;
use Functional\Booking\Actions\DepartReservation;
use Functional\Booking\Actions\ReturnReservation;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Enums\ReturnCondition;
use Functional\Booking\Exceptions\DepartureRefusedException;
use Functional\Booking\Exceptions\IllegalReservationTransitionException;
use Functional\Booking\Exceptions\MachineNotReservableException;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Machine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Machine $machine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-10 09:00'));
        $this->machine = Machine::factory()->create();
    }

    public function test_departure_puts_the_reservation_in_progress_and_the_machine_rented_out(): void
    {
        $reservation = $this->reservation('2026-11-10', '2026-11-14');

        app(DepartReservation::class)->handle($reservation);

        $this->assertSame(ReservationStatus::InProgress, $reservation->fresh()?->status);
        $this->assertNotNull($reservation->fresh()?->departed_at);
        $this->assertSame(MachineStatus::RentedOut, $this->machine->fresh()?->status);
    }

    public function test_departure_is_refused_before_the_start_date(): void
    {
        $reservation = $this->reservation('2026-11-11', '2026-11-14');

        $this->expectException(DepartureRefusedException::class);
        $this->expectExceptionMessage('11/11/2026');

        app(DepartReservation::class)->handle($reservation);
    }

    public function test_departure_is_refused_when_the_vgp_no_longer_covers_the_end_date(): void
    {
        $this->machine->update(['is_subject_to_vgp' => true, 'vgp_due_date' => '2026-11-30']);
        $reservation = $this->reservation('2026-11-10', '2026-11-14');
        $this->machine->update(['vgp_due_date' => '2026-11-12']);

        $this->expectException(DepartureRefusedException::class);
        $this->expectExceptionMessage('12/11/2026');

        app(DepartReservation::class)->handle($reservation);
    }

    public function test_departure_is_refused_when_the_machine_is_not_available(): void
    {
        $reservation = $this->reservation('2026-11-10', '2026-11-14');
        $this->machine->update(['status' => MachineStatus::Workshop]);

        $this->expectException(DepartureRefusedException::class);
        $this->expectExceptionMessage('« Atelier »');

        app(DepartReservation::class)->handle($reservation);
    }

    public function test_a_return_in_good_state_closes_the_reservation_and_frees_the_machine(): void
    {
        $reservation = $this->departedReservation('2026-11-10', '2026-11-14');
        $this->travelTo(CarbonImmutable::parse('2026-11-14 17:00'));

        app(ReturnReservation::class)->handle($reservation, ReturnCondition::GoodState);

        $this->assertSame(ReservationStatus::Closed, $reservation->fresh()?->status);
        $this->assertNotNull($reservation->fresh()?->returned_at);
        $this->assertSame(MachineStatus::Available, $this->machine->fresh()?->status);
    }

    public function test_a_return_to_the_workshop_makes_the_machine_unreservable(): void
    {
        $reservation = $this->departedReservation('2026-11-10', '2026-11-14');
        $this->travelTo(CarbonImmutable::parse('2026-11-14 17:00'));

        app(ReturnReservation::class)->handle($reservation, ReturnCondition::Workshop);

        $this->assertSame(ReservationStatus::Closed, $reservation->fresh()?->status);
        $this->assertSame(MachineStatus::Workshop, $this->machine->fresh()?->status);
        $this->expectException(MachineNotReservableException::class);
        $this->reservation('2026-11-20', '2026-11-22');
    }

    public function test_an_early_return_brings_the_end_date_back_and_frees_the_remaining_days(): void
    {
        $reservation = $this->departedReservation('2026-11-10', '2026-11-14');
        $this->travelTo(CarbonImmutable::parse('2026-11-12 17:00'));

        app(ReturnReservation::class)->handle($reservation, ReturnCondition::GoodState);

        $this->assertSame('2026-11-12', $reservation->fresh()?->end_date->toDateString());
        $this->assertSame('2026-11-14', $reservation->fresh()?->planned_end_date->toDateString());
        $this->assertTrue($this->reservation('2026-11-13', '2026-11-14')->exists);
    }

    public function test_a_late_return_keeps_the_end_date_even_when_the_next_reservation_has_started(): void
    {
        $reservation = $this->departedReservation('2026-11-10', '2026-11-14');
        $nextReservation = $this->reservation('2026-11-15', '2026-11-18');
        $this->travelTo(CarbonImmutable::parse('2026-11-16 08:00'));

        app(ReturnReservation::class)->handle($reservation, ReturnCondition::GoodState);

        $this->assertSame('2026-11-14', $reservation->fresh()?->end_date->toDateString());
        $this->assertSame('2026-11-16', $reservation->fresh()?->returned_at?->toDateString());
        $this->assertSame(ReservationStatus::Confirmed, $nextReservation->fresh()?->status);
    }

    public function test_cancelling_a_confirmed_reservation_frees_its_dates(): void
    {
        $reservation = $this->reservation('2026-11-12', '2026-11-14');

        app(CancelReservation::class)->handle($reservation);

        $this->assertSame(ReservationStatus::Cancelled, $reservation->fresh()?->status);
        $this->assertTrue($this->reservation('2026-11-12', '2026-11-14')->exists);
    }

    public function test_a_departed_reservation_cannot_be_cancelled(): void
    {
        $reservation = $this->departedReservation('2026-11-10', '2026-11-14');

        $this->expectException(IllegalReservationTransitionException::class);

        app(CancelReservation::class)->handle($reservation);
    }

    private function departedReservation(string $startDate, string $endDate): Reservation
    {
        $reservation = $this->reservation($startDate, $endDate);

        return app(DepartReservation::class)->handle($reservation);
    }

    private function reservation(string $startDate, string $endDate): Reservation
    {
        return app(CreateReservation::class)->handle(
            User::factory()->employee()->create(),
            $this->machine->fresh() ?? $this->machine,
            Customer::factory()->create(),
            CarbonImmutable::parse($startDate),
            CarbonImmutable::parse($endDate),
        );
    }
}
