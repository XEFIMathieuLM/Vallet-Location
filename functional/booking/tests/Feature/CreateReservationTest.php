<?php

namespace Functional\Booking\Tests\Feature;

use Carbon\CarbonImmutable;
use Database\Seeders\PermissionSeeder;
use Functional\Booking\Actions\CreateReservation;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Events\ReservationChanged;
use Functional\Booking\Exceptions\InvalidReservationDatesException;
use Functional\Booking\Exceptions\ReservationOverlapException;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Booking\Queries\AvailableMachinesQuery;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateReservationTest extends TestCase
{
    use AssertsRefusals, CreatesUsers, RefreshDatabase;

    private Machine $machine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-01'));
        $this->machine = Machine::factory()->create();
    }

    public function test_an_accepted_reservation_is_recorded_and_visible_to_every_agency(): void
    {
        Event::fake([ReservationChanged::class]);
        $author = $this->employee();

        $reservation = $this->reserve($author, '2026-11-10', '2026-11-14');

        $this->assertSame(ReservationStatus::Confirmed, $reservation->status);
        $this->assertSame($author->agency_id, $reservation->agency_id);
        $this->assertSame($author->id, $reservation->created_by);
        $this->assertTrue($reservation->planned_end_date->equalTo($reservation->end_date));
        Event::assertDispatched(ReservationChanged::class, fn (ReservationChanged $event): bool => $event->reservation->is($reservation));

        $availableMachines = app(AvailableMachinesQuery::class)->get(
            CarbonImmutable::parse('2026-11-12'),
            CarbonImmutable::parse('2026-11-12'),
        );
        $this->assertFalse($availableMachines->contains($this->machine));
    }

    public function test_an_overlapping_reservation_is_refused_with_the_conflicting_dates_and_agency(): void
    {
        $firstAgency = Agency::factory()->create(['name' => 'Annecy']);
        $this->reserve($this->employee(['agency_id' => $firstAgency->id]), '2026-11-10', '2026-11-14');

        $this->assertRefused(ReservationOverlapException::class, 'du 10/11/2026 au 14/11/2026 par l\'agence Annecy', fn () => $this->reserve($this->employee(), '2026-11-13', '2026-11-16'));
    }

    public function test_a_reservation_sharing_a_single_day_overlaps(): void
    {
        $this->reserve($this->employee(), '2026-11-10', '2026-11-14');

        $this->expectException(ReservationOverlapException::class);

        $this->reserve($this->employee(), '2026-11-14', '2026-11-14');
    }

    public function test_an_adjacent_reservation_is_accepted(): void
    {
        $this->reserve($this->employee(), '2026-11-10', '2026-11-14');

        $this->reserve($this->employee(), '2026-11-15', '2026-11-18');

        $this->assertSame(2, Reservation::query()->count());
    }

    public function test_a_cancelled_reservation_does_not_block_its_dates(): void
    {
        Reservation::factory()
            ->for($this->machine)
            ->between(CarbonImmutable::parse('2026-11-10'), CarbonImmutable::parse('2026-11-14'))
            ->withStatus(ReservationStatus::Cancelled)
            ->create();

        $this->reserve($this->employee(), '2026-11-10', '2026-11-14');

        $this->assertSame(2, Reservation::query()->count());
    }

    public function test_a_reservation_ending_before_it_starts_is_refused(): void
    {
        $this->assertRefused(InvalidReservationDatesException::class, __('booking::reservations.refusals.end_before_start'), fn () => $this->reserve($this->employee(), '2026-11-14', '2026-11-10'));
    }

    public function test_a_reservation_starting_in_the_past_is_refused(): void
    {
        $this->assertRefused(InvalidReservationDatesException::class, __('booking::reservations.refusals.start_in_the_past'), fn () => $this->reserve($this->employee(), '2026-10-31', '2026-11-02'));
    }

    public function test_a_single_day_reservation_starting_today_is_accepted(): void
    {
        $reservation = $this->reserve($this->employee(), '2026-11-01', '2026-11-01');

        $this->assertTrue($reservation->exists);
    }

    private function reserve(Authenticatable&AgencyMember $author, string $startDate, string $endDate): Reservation
    {
        return app(CreateReservation::class)->handle(
            $author,
            $this->machine,
            Customer::factory()->create(),
            CarbonImmutable::parse($startDate),
            CarbonImmutable::parse($endDate),
        );
    }
}
