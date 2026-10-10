<?php

namespace Functional\Booking\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Actions\DepartReservation;
use Functional\Booking\Actions\ReturnReservation;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Enums\ReturnCondition;
use Functional\Booking\Extensions\ReservationTransitionGuards;
use Functional\Booking\Models\Reservation;
use Functional\Booking\Tests\Doubles\GuardRefusalException;
use Functional\Booking\Tests\Doubles\RefusingGuard;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Machine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTransitionGuardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-10 09:00'));
    }

    public function test_a_refusing_guard_blocks_the_departure_without_changing_anything(): void
    {
        app(ReservationTransitionGuards::class)->register(RefusingGuard::class);
        $reservation = $this->reservation(MachineStatus::Available, ReservationStatus::Confirmed);

        $this->assertRefused(fn () => app(DepartReservation::class)->handle($reservation));

        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()?->status);
        $this->assertNull($reservation->fresh()?->departed_at);
        $this->assertSame(MachineStatus::Available, $reservation->machine->fresh()?->status);
    }

    public function test_a_refusing_guard_blocks_the_return_without_changing_anything(): void
    {
        app(ReservationTransitionGuards::class)->register(RefusingGuard::class);
        $reservation = $this->reservation(MachineStatus::RentedOut, ReservationStatus::InProgress);

        $this->assertRefused(fn () => app(ReturnReservation::class)->handle($reservation, ReturnCondition::GoodState));

        $this->assertSame(ReservationStatus::InProgress, $reservation->fresh()?->status);
        $this->assertNull($reservation->fresh()?->returned_at);
        $this->assertSame(MachineStatus::RentedOut, $reservation->machine->fresh()?->status);
    }

    public function test_without_guard_the_departure_and_return_are_unchanged(): void
    {
        $reservation = $this->reservation(MachineStatus::Available, ReservationStatus::Confirmed);

        app(DepartReservation::class)->handle($reservation);
        app(ReturnReservation::class)->handle($reservation, ReturnCondition::GoodState);

        $this->assertSame(ReservationStatus::Closed, $reservation->fresh()?->status);
    }

    private function assertRefused(callable $transition): void
    {
        $refusal = rescue($transition, fn ($exception) => $exception, report: false);

        $this->assertInstanceOf(GuardRefusalException::class, $refusal);
    }

    private function reservation(MachineStatus $machineStatus, ReservationStatus $status): Reservation
    {
        return Reservation::factory()
            ->for(Machine::factory()->withStatus($machineStatus))
            ->between(CarbonImmutable::parse('2026-11-10'), CarbonImmutable::parse('2026-11-14'))
            ->withStatus($status)
            ->create();
    }
}
