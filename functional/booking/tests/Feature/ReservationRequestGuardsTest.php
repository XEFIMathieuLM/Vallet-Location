<?php

namespace Functional\Booking\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Actions\CreateReservation;
use Functional\Booking\Extensions\ReservationRequestGuards;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Booking\Queries\AvailableMachinesQuery;
use Functional\Booking\Tests\Doubles\GuardRefusalException;
use Functional\Booking\Tests\Doubles\RefusingRequestGuard;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationRequestGuardsTest extends TestCase
{
    use AssertsRefusals, CreatesUsers, RefreshDatabase;

    private Machine $machine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
        $this->travelTo(CarbonImmutable::parse('2026-11-01'));
        $this->machine = Machine::factory()->create();
    }

    public function test_a_registered_guard_refuses_the_reservation_and_nothing_is_recorded(): void
    {
        app(ReservationRequestGuards::class)->register(RefusingRequestGuard::class);

        $this->assertRefused(GuardRefusalException::class, 'Machine bloquée par une autre fonctionnalité.', fn () => $this->reserve());

        $this->assertSame(0, Reservation::query()->count());
    }

    public function test_a_registered_guard_removes_the_machine_from_the_availability_search(): void
    {
        app(ReservationRequestGuards::class)->register(RefusingRequestGuard::class);

        $this->assertFalse($this->availableMachines()->contains($this->machine));
    }

    public function test_without_any_guard_the_reservation_and_the_search_are_unchanged(): void
    {
        $this->reserve();

        $this->assertSame(1, Reservation::query()->count());
        $this->assertTrue($this->availableMachines()->contains($this->machine));
    }

    private function reserve(): Reservation
    {
        return app(CreateReservation::class)->handle(
            $this->employee(),
            $this->machine,
            Customer::factory()->create(),
            CarbonImmutable::parse('2026-11-10'),
            CarbonImmutable::parse('2026-11-14'),
        );
    }

    /**
     * @return Collection<int, Machine>
     */
    private function availableMachines(): Collection
    {
        return app(AvailableMachinesQuery::class)->get(CarbonImmutable::parse('2026-11-20'), CarbonImmutable::parse('2026-11-22'));
    }
}
