<?php

namespace Functional\Booking\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ConflictReason;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Booking\Queries\DayOperations;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DayOperationsQueryTest extends TestCase
{
    use RefreshDatabase;

    private CarbonImmutable $today;

    private Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00'));
        $this->today = CarbonImmutable::today();
        $this->agency = Agency::factory()->create();
    }

    public function test_departures_are_confirmed_reservations_starting_today_or_earlier(): void
    {
        $today = $this->reservation(ReservationStatus::Confirmed, '2026-10-10', '2026-10-12');
        $missed = $this->reservation(ReservationStatus::Confirmed, '2026-10-08', '2026-10-12');
        $this->reservation(ReservationStatus::Confirmed, '2026-10-11', '2026-10-12');
        $this->reservation(ReservationStatus::InProgress, '2026-10-10', '2026-10-12');

        $this->assertSame([$missed->id, $today->id], $this->ids(app(DayOperations::class)->departures($this->agency->id, $this->today)));
    }

    public function test_departures_of_the_same_day_are_sorted_by_machine_reference(): void
    {
        $second = $this->reservation(ReservationStatus::Confirmed, '2026-10-10', '2026-10-12', 'NAC-0200');
        $first = $this->reservation(ReservationStatus::Confirmed, '2026-10-10', '2026-10-12', 'NAC-0100');

        $this->assertSame([$first->id, $second->id], $this->ids(app(DayOperations::class)->departures($this->agency->id, $this->today)));
    }

    public function test_upcoming_departures_cover_tomorrow_to_the_horizon_included(): void
    {
        $tomorrow = $this->reservation(ReservationStatus::Confirmed, '2026-10-11', '2026-10-20');
        $lastDay = $this->reservation(ReservationStatus::Confirmed, '2026-10-17', '2026-10-20');
        $this->reservation(ReservationStatus::Confirmed, '2026-10-10', '2026-10-20');
        $this->reservation(ReservationStatus::Confirmed, '2026-10-18', '2026-10-20');
        $this->reservation(ReservationStatus::Cancelled, '2026-10-12', '2026-10-20');

        $this->assertSame([$tomorrow->id, $lastDay->id], $this->ids(app(DayOperations::class)->upcomingDepartures($this->agency->id, $this->today, 7)));
    }

    public function test_returns_are_reservations_in_progress_ending_today(): void
    {
        $returning = $this->reservation(ReservationStatus::InProgress, '2026-10-05', '2026-10-10');
        $this->reservation(ReservationStatus::InProgress, '2026-10-05', '2026-10-11');
        $this->reservation(ReservationStatus::Confirmed, '2026-10-10', '2026-10-10');

        $this->assertSame([$returning->id], $this->ids(app(DayOperations::class)->returns($this->agency->id, $this->today)));
    }

    public function test_a_single_day_reservation_departs_then_returns_the_same_day(): void
    {
        $reservation = $this->reservation(ReservationStatus::Confirmed, '2026-10-10', '2026-10-10');
        $dayOperations = app(DayOperations::class);

        $this->assertSame([$reservation->id], $this->ids($dayOperations->departures($this->agency->id, $this->today)));
        $this->assertSame([], $this->ids($dayOperations->returns($this->agency->id, $this->today)));

        $reservation->update(['status' => ReservationStatus::InProgress, 'departed_at' => $this->today]);

        $this->assertSame([], $this->ids($dayOperations->departures($this->agency->id, $this->today)));
        $this->assertSame([$reservation->id], $this->ids($dayOperations->returns($this->agency->id, $this->today)));
    }

    public function test_late_returns_are_reservations_in_progress_past_their_end_oldest_first(): void
    {
        $yesterday = $this->reservation(ReservationStatus::InProgress, '2026-10-01', '2026-10-09');
        $lastWeek = $this->reservation(ReservationStatus::InProgress, '2026-09-25', '2026-10-03');
        $this->reservation(ReservationStatus::InProgress, '2026-10-01', '2026-10-10');
        $this->reservation(ReservationStatus::Closed, '2026-09-25', '2026-10-03');

        $this->assertSame([$lastWeek->id, $yesterday->id], $this->ids(app(DayOperations::class)->lateReturns($this->agency->id, $this->today)));
    }

    public function test_conflicts_are_confirmed_reservations_with_a_conflict_reason_nearest_first(): void
    {
        $later = $this->reservation(ReservationStatus::Confirmed, '2026-10-20', '2026-10-22', attributes: ['conflict_reason' => ConflictReason::VgpExpired]);
        $sooner = $this->reservation(ReservationStatus::Confirmed, '2026-10-12', '2026-10-14', attributes: ['conflict_reason' => ConflictReason::MachineUnavailable]);
        $this->reservation(ReservationStatus::Confirmed, '2026-10-12', '2026-10-14');
        $this->reservation(ReservationStatus::Cancelled, '2026-10-12', '2026-10-14');

        $this->assertSame([$sooner->id, $later->id], $this->ids(app(DayOperations::class)->conflicts($this->agency->id)));
    }

    public function test_a_reservation_belongs_to_the_home_agency_of_its_machine_not_to_the_creating_agency(): void
    {
        $otherAgency = Agency::factory()->create();
        $createdElsewhere = $this->reservation(ReservationStatus::Confirmed, '2026-10-10', '2026-10-12', attributes: ['agency_id' => $otherAgency->id]);
        $otherMachine = Machine::factory()->create();
        $foreign = Reservation::factory()->for($otherMachine)->between($this->today, $this->today->addDays(2))->create(['agency_id' => $this->agency->id]);
        $dayOperations = app(DayOperations::class);

        $this->assertSame([$createdElsewhere->id], $this->ids($dayOperations->departures($this->agency->id, $this->today)));
        $this->assertEqualsCanonicalizing([$createdElsewhere->id, $foreign->id], $this->ids($dayOperations->departures(null, $this->today)));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function reservation(ReservationStatus $status, string $start, string $end, ?string $reference = null, array $attributes = []): Reservation
    {
        $machine = Machine::factory()->for($this->agency)->create($reference !== null ? ['reference' => $reference] : []);

        return Reservation::factory()
            ->for($machine)
            ->between(CarbonImmutable::parse($start), CarbonImmutable::parse($end))
            ->withStatus($status)
            ->create($attributes);
    }

    /**
     * @param  Builder<Reservation>  $reservations
     * @return list<int>
     */
    private function ids(Builder $reservations): array
    {
        return $reservations->pluck('id')->all();
    }
}
