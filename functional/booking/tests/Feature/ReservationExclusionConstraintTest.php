<?php

namespace Functional\Booking\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReservationExclusionConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_database_refuses_an_overlapping_reservation_inserted_directly(): void
    {
        $machine = Machine::factory()->create();
        $this->insertReservation($machine, ReservationStatus::Confirmed);

        $refusal = rescue(fn () => DB::transaction(fn () => $this->insertReservation($machine, ReservationStatus::Confirmed)), fn ($exception) => $exception, report: false);

        $this->assertInstanceOf(QueryException::class, $refusal);
        $this->assertSame('23P01', $refusal->getCode());
        $this->assertSame(1, Reservation::query()->count());
    }

    public function test_the_database_accepts_an_overlap_with_a_cancelled_reservation(): void
    {
        $machine = Machine::factory()->create();
        $this->insertReservation($machine, ReservationStatus::Cancelled);

        $this->insertReservation($machine, ReservationStatus::Confirmed);

        $this->assertSame(2, Reservation::query()->count());
    }

    public function test_the_database_accepts_the_same_dates_on_another_machine(): void
    {
        $this->insertReservation(Machine::factory()->create(), ReservationStatus::Confirmed);

        $this->insertReservation(Machine::factory()->create(), ReservationStatus::Confirmed);

        $this->assertSame(2, Reservation::query()->count());
    }

    private function insertReservation(Machine $machine, ReservationStatus $status): Reservation
    {
        return Reservation::factory()
            ->for($machine)
            ->between(CarbonImmutable::parse('2030-11-10'), CarbonImmutable::parse('2030-11-14'))
            ->withStatus($status)
            ->create();
    }
}
