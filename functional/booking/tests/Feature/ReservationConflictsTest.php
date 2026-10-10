<?php

namespace Functional\Booking\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Actions\ReturnReservation;
use Functional\Booking\Enums\ConflictReason;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Enums\ReturnCondition;
use Functional\Booking\Events\ReservationChanged;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Actions\ChangeMachineStatus;
use Functional\Fleet\Actions\UpdateMachineVgp;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Enums\MachineTransition;
use Functional\Fleet\Models\Machine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ReservationConflictsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-10 09:00'));
    }

    public function test_a_machine_sent_to_the_workshop_puts_upcoming_reservations_in_conflict_without_deleting_them(): void
    {
        Event::fake([ReservationChanged::class]);
        $machine = Machine::factory()->create();
        $upcomingReservation = $this->reservation($machine, '2026-11-20', '2026-11-22');
        $cancelledReservation = $this->reservation($machine, '2026-11-25', '2026-11-26', ReservationStatus::Cancelled);

        app(ChangeMachineStatus::class)->handle($machine, MachineTransition::SendToWorkshop);

        $this->assertSame(ConflictReason::MachineUnavailable, $upcomingReservation->fresh()?->conflict_reason);
        $this->assertNull($cancelledReservation->fresh()?->conflict_reason);
        $this->assertSame(2, Reservation::query()->count());
        Event::assertDispatched(ReservationChanged::class, fn (ReservationChanged $event): bool => $event->reservation->is($upcomingReservation));
    }

    public function test_the_conflict_is_lifted_when_the_machine_becomes_available_again(): void
    {
        $machine = Machine::factory()->create();
        $upcomingReservation = $this->reservation($machine, '2026-11-20', '2026-11-22');
        app(ChangeMachineStatus::class)->handle($machine, MachineTransition::MarkOutOfOrder);

        app(ChangeMachineStatus::class)->handle($machine, MachineTransition::MakeAvailable);

        $this->assertNull($upcomingReservation->fresh()?->conflict_reason);
    }

    public function test_a_vgp_date_no_longer_covering_the_period_puts_the_reservation_in_conflict(): void
    {
        $machine = Machine::factory()->subjectToVgpUntil(CarbonImmutable::parse('2026-12-31'))->create();
        $coveredReservation = $this->reservation($machine, '2026-11-12', '2026-11-14');
        $uncoveredReservation = $this->reservation($machine, '2026-11-20', '2026-11-22');

        app(UpdateMachineVgp::class)->handle($machine, CarbonImmutable::parse('2026-11-15'));

        $this->assertNull($coveredReservation->fresh()?->conflict_reason);
        $this->assertSame(ConflictReason::VgpExpired, $uncoveredReservation->fresh()?->conflict_reason);

        app(UpdateMachineVgp::class)->handle($machine, CarbonImmutable::parse('2027-06-30'));

        $this->assertNull($uncoveredReservation->fresh()?->conflict_reason);
    }

    public function test_an_overdue_rental_puts_the_next_reservation_in_conflict_until_the_machine_is_returned(): void
    {
        $machine = Machine::factory()->withStatus(MachineStatus::RentedOut)->create();
        $overdueRental = $this->reservation($machine, '2026-11-01', '2026-11-08', ReservationStatus::InProgress);
        $nextReservation = $this->reservation($machine, '2026-11-12', '2026-11-14');
        $laterReservation = $this->reservation($machine, '2026-11-20', '2026-11-22');

        $this->artisan('booking:flag-late-returns')->assertSuccessful();

        $this->assertSame(ConflictReason::MachineNotReturned, $nextReservation->fresh()?->conflict_reason);
        $this->assertNull($laterReservation->fresh()?->conflict_reason);

        app(ReturnReservation::class)->handle($overdueRental, ReturnCondition::GoodState);

        $this->assertNull($nextReservation->fresh()?->conflict_reason);
    }

    private function reservation(Machine $machine, string $startDate, string $endDate, ReservationStatus $status = ReservationStatus::Confirmed): Reservation
    {
        return Reservation::factory()
            ->for($machine)
            ->between(CarbonImmutable::parse($startDate), CarbonImmutable::parse($endDate))
            ->withStatus($status)
            ->create();
    }
}
