<?php

namespace Functional\Booking\Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissionSeeder;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Livewire\AvailabilitySearch;
use Functional\Booking\Models\Reservation;
use Functional\Booking\Queries\AvailableMachinesQuery;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AvailabilitySearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-01'));
    }

    public function test_a_machine_reserved_on_the_period_is_not_available(): void
    {
        $reservedMachine = Machine::factory()->create();
        $freeMachine = Machine::factory()->create();
        $this->reserveBetween($reservedMachine, '2026-11-10', '2026-11-14', ReservationStatus::Confirmed);

        $availableMachines = $this->search('2026-11-14', '2026-11-20');

        $this->assertTrue($availableMachines->contains($freeMachine));
        $this->assertFalse($availableMachines->contains($reservedMachine));
    }

    public function test_a_machine_reserved_outside_the_period_or_cancelled_is_available(): void
    {
        $machineReservedLater = Machine::factory()->create();
        $machineWithCancellation = Machine::factory()->create();
        $this->reserveBetween($machineReservedLater, '2026-11-21', '2026-11-25', ReservationStatus::Confirmed);
        $this->reserveBetween($machineWithCancellation, '2026-11-10', '2026-11-14', ReservationStatus::Cancelled);

        $availableMachines = $this->search('2026-11-14', '2026-11-20');

        $this->assertTrue($availableMachines->contains($machineReservedLater));
        $this->assertTrue($availableMachines->contains($machineWithCancellation));
    }

    public function test_the_search_filters_by_category_and_home_agency(): void
    {
        $category = MachineCategory::factory()->create();
        $agency = Agency::factory()->create();
        $matchingMachine = Machine::factory()->for($category, 'category')->for($agency)->create();
        $machineOfAnotherCategory = Machine::factory()->for($agency)->create();
        $machineOfAnotherAgency = Machine::factory()->for($category, 'category')->create();

        $availableMachines = $this->search('2026-11-14', '2026-11-20', $category, $agency);

        $this->assertTrue($availableMachines->contains($matchingMachine));
        $this->assertFalse($availableMachines->contains($machineOfAnotherCategory));
        $this->assertFalse($availableMachines->contains($machineOfAnotherAgency));
    }

    public function test_the_screen_lists_available_machines_with_a_link_to_reserve(): void
    {
        $this->seed(PermissionSeeder::class);
        $machine = Machine::factory()->create(['reference' => 'NAC-0042']);

        $this->actingAs(User::factory()->employee()->create())
            ->get(route('availability.index', ['du' => '2026-11-10', 'au' => '2026-11-14']))
            ->assertOk()
            ->assertSee('NAC-0042')
            ->assertSee(route('reservations.create', ['machine' => $machine->id, 'du' => '2026-11-10', 'au' => '2026-11-14']));
    }

    public function test_the_screen_hides_a_machine_reserved_on_the_filtered_period(): void
    {
        $this->seed(PermissionSeeder::class);
        $machine = Machine::factory()->create(['reference' => 'NAC-0042']);
        $this->reserveBetween($machine, '2026-11-10', '2026-11-14', ReservationStatus::Confirmed);

        Livewire::actingAs(User::factory()->employee()->create())
            ->test(AvailabilitySearch::class)
            ->set('startDate', '2026-11-01')
            ->set('endDate', '2026-11-05')
            ->assertSee('NAC-0042')
            ->set('endDate', '2026-11-10')
            ->assertDontSee('NAC-0042');
    }

    public function test_the_screen_is_refused_without_the_reservation_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('availability.index'))
            ->assertForbidden();
    }

    /**
     * @return Collection<int, Machine>
     */
    private function search(string $startDate, string $endDate, ?MachineCategory $category = null, ?Agency $agency = null): Collection
    {
        return app(AvailableMachinesQuery::class)->get(
            CarbonImmutable::parse($startDate),
            CarbonImmutable::parse($endDate),
            $category?->id,
            $agency?->id,
        );
    }

    private function reserveBetween(Machine $machine, string $startDate, string $endDate, ReservationStatus $status): void
    {
        Reservation::factory()
            ->for($machine)
            ->between(CarbonImmutable::parse($startDate), CarbonImmutable::parse($endDate))
            ->withStatus($status)
            ->create();
    }
}
