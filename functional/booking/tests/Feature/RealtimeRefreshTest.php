<?php

namespace Functional\Booking\Tests\Feature;

use Carbon\CarbonImmutable;
use Database\Seeders\PermissionSeeder;
use Functional\Booking\Livewire\AvailabilitySearch;
use Functional\Booking\Livewire\Planning;
use Functional\Booking\Livewire\ReservationList;
use Functional\Booking\Models\Reservation;
use Functional\Booking\Tests\Concerns\WithoutTransitionExtensions;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Livewire\MachineIndex;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RealtimeRefreshTest extends TestCase
{
    use CreatesUsers, RefreshDatabase, WithoutTransitionExtensions;

    private Model&Authenticatable&AgencyMember $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-10 09:00'));
        $this->employee = $this->employee();
    }

    public function test_the_availability_search_refreshes_when_another_agency_reserves(): void
    {
        $machine = Machine::factory()->create(['reference' => 'NAC-0042']);
        $search = Livewire::actingAs($this->employee)->test(AvailabilitySearch::class)->assertSee('NAC-0042');

        $this->reserve($machine);

        $search->dispatch('echo-private:fleet,.reservation.changed')->assertDontSee('NAC-0042');
    }

    public function test_the_reservation_list_refreshes_on_a_reservation_change(): void
    {
        $list = Livewire::actingAs($this->employee)->test(ReservationList::class)->assertDontSee('NAC-0042');

        $this->reserve(Machine::factory()->create(['reference' => 'NAC-0042']));

        $list->dispatch('echo-private:fleet,.reservation.changed')->assertSee('NAC-0042');
    }

    public function test_the_fleet_screen_refreshes_on_a_machine_change_and_an_import(): void
    {
        $machine = Machine::factory()->create(['reference' => 'NAC-0042']);
        $fleet = Livewire::actingAs($this->employee)
            ->test(MachineIndex::class)
            ->set('status', MachineStatus::OutOfOrder->value)
            ->assertDontSee('NAC-0042');

        $machine->update(['status' => MachineStatus::OutOfOrder]);
        $fleet->dispatch('echo-private:fleet,.machine.changed')->assertSee('NAC-0042');

        Machine::factory()->withStatus(MachineStatus::OutOfOrder)->create(['reference' => 'IMP-0001']);
        $fleet->dispatch('echo-private:fleet,.fleet.imported')->assertSee('IMP-0001');
    }

    public function test_the_planning_refreshes_on_a_reservation_change(): void
    {
        $machine = Machine::factory()->create();
        $planning = Livewire::actingAs($this->employee)->test(Planning::class);

        $reservation = $this->reserve($machine);

        $planning->dispatch('echo-private:fleet,.reservation.changed')->assertSee($reservation->customer->name);
    }

    private function reserve(Machine $machine): Reservation
    {
        return Reservation::factory()
            ->for($machine)
            ->between(CarbonImmutable::parse('2026-11-10'), CarbonImmutable::parse('2026-11-12'))
            ->create();
    }
}
