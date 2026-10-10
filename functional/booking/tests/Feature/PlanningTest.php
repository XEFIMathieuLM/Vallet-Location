<?php

namespace Functional\Booking\Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissionSeeder;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Livewire\Planning;
use Functional\Booking\Models\Reservation;
use Functional\Booking\Planning\PlanningCellKind;
use Functional\Booking\Planning\PlanningGrid;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlanningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-10 09:00'));
    }

    public function test_each_machine_shows_its_reservations_day_by_day(): void
    {
        $machine = Machine::factory()->create();
        $reservation = $this->reservation($machine, '2026-11-11', '2026-11-12', ReservationStatus::Confirmed);
        $this->reservation($machine, '2026-11-13', '2026-11-13', ReservationStatus::Cancelled);

        $row = $this->grid('2026-11-10', '2026-11-14')->rows[0];

        $this->assertSame(PlanningCellKind::Free, $row->cells['2026-11-10']->kind);
        $this->assertSame(PlanningCellKind::Reserved, $row->cells['2026-11-11']->kind);
        $this->assertTrue($reservation->is($row->cells['2026-11-12']->reservation));
        $this->assertSame(PlanningCellKind::Free, $row->cells['2026-11-13']->kind);
    }

    public function test_workshop_out_of_order_and_invalid_vgp_days_are_shown_as_unavailable(): void
    {
        $workshopMachine = Machine::factory()->withStatus(MachineStatus::Workshop)->create(['reference' => 'A-WORKSHOP']);
        $outOfOrderMachine = Machine::factory()->withStatus(MachineStatus::OutOfOrder)->create(['reference' => 'B-OUT-OF-ORDER']);
        $expiringMachine = Machine::factory()->subjectToVgpUntil(CarbonImmutable::parse('2026-11-12'))->create(['reference' => 'C-VGP']);

        $rows = collect($this->grid('2026-11-10', '2026-11-14')->rows)->keyBy(fn ($row) => $row->machine->reference);

        $this->assertSame(PlanningCellKind::Workshop, $rows['A-WORKSHOP']->cells['2026-11-14']->kind);
        $this->assertSame(PlanningCellKind::OutOfOrder, $rows['B-OUT-OF-ORDER']->cells['2026-11-10']->kind);
        $this->assertSame(PlanningCellKind::Free, $rows['C-VGP']->cells['2026-11-12']->kind);
        $this->assertSame(PlanningCellKind::VgpInvalid, $rows['C-VGP']->cells['2026-11-13']->kind);
        $this->assertTrue($workshopMachine->is($rows['A-WORKSHOP']->machine));
        $this->assertTrue($outOfOrderMachine->is($rows['B-OUT-OF-ORDER']->machine));
        $this->assertTrue($expiringMachine->is($rows['C-VGP']->machine));
    }

    public function test_the_planning_filters_by_category_and_agency_and_hides_retired_machines(): void
    {
        $category = MachineCategory::factory()->create();
        $agency = Agency::factory()->create();
        $matchingMachine = Machine::factory()->for($category, 'category')->for($agency)->create();
        Machine::factory()->for($agency)->create();
        Machine::factory()->for($category, 'category')->create();
        Machine::factory()->for($category, 'category')->for($agency)->withStatus(MachineStatus::Retired)->create();

        $rows = $this->grid('2026-11-10', '2026-11-14', $category->id, $agency->id)->rows;

        $this->assertCount(1, $rows);
        $this->assertTrue($matchingMachine->is($rows[0]->machine));
    }

    public function test_the_planning_screen_renders_the_period(): void
    {
        $this->seed(PermissionSeeder::class);
        $machine = Machine::factory()->create(['reference' => 'NAC-0042']);
        $this->reservation($machine, '2026-11-11', '2026-11-12', ReservationStatus::InProgress);

        Livewire::actingAs(User::factory()->employee()->create())
            ->withQueryParams(['du' => '2026-11-10', 'au' => '2026-11-16'])
            ->test(Planning::class)
            ->assertSee('NAC-0042')
            ->assertSee('16/11');

        $this->actingAs(User::factory()->employee()->create())->get(route('planning.index'))->assertOk();
    }

    private function grid(string $startDate, string $endDate, ?int $categoryId = null, ?int $agencyId = null): PlanningGrid
    {
        return PlanningGrid::build(
            PlanningGrid::machinesQuery($categoryId, $agencyId)->get(),
            CarbonImmutable::parse($startDate),
            CarbonImmutable::parse($endDate),
        );
    }

    private function reservation(Machine $machine, string $startDate, string $endDate, ReservationStatus $status): Reservation
    {
        return Reservation::factory()
            ->for($machine)
            ->between(CarbonImmutable::parse($startDate), CarbonImmutable::parse($endDate))
            ->withStatus($status)
            ->create();
    }
}
