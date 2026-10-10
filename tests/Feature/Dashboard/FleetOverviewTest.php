<?php

namespace Tests\Feature\Dashboard;

use App\Livewire\Dashboard\FleetStatus;
use App\Livewire\Dashboard\VgpWatch;
use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Booking\Access\BookingPermission;
use Functional\Fleet\Access\FleetPermission;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Feature\Dashboard\Concerns\BuildsDashboardFixtures;
use Tests\TestCase;

class FleetOverviewTest extends TestCase
{
    use BuildsDashboardFixtures, RefreshDatabase;

    private Agency $rouen;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00'));
        $this->rouen = $this->agencyNamed('Rouen');
        $this->employee = $this->employeeOf($this->rouen);
    }

    public function test_the_fleet_status_counts_the_machines_of_the_agency_without_retired_ones(): void
    {
        Machine::factory()->for($this->rouen)->count(5)->create();
        Machine::factory()->for($this->rouen)->withStatus(MachineStatus::RentedOut)->count(3)->create();
        Machine::factory()->for($this->rouen)->withStatus(MachineStatus::Workshop)->create();
        Machine::factory()->for($this->rouen)->withStatus(MachineStatus::OutOfOrder)->create();
        Machine::factory()->for($this->rouen)->withStatus(MachineStatus::Retired)->count(2)->create();
        Machine::factory()->withStatus(MachineStatus::Workshop)->count(4)->create();

        $this->assertSame(['available' => 5, 'rented_out' => 3, 'workshop' => 1, 'out_of_order' => 1], $this->fleetStatus()->viewData('counts'));
        $this->fleetStatus()->assertDontSee(MachineStatus::Retired->label());
    }

    public function test_the_vgp_watch_lists_missing_then_expired_then_soon_expiring_vgp(): void
    {
        $this->machineIn($this->rouen, ['reference' => 'NAC-0118'])->update(['is_subject_to_vgp' => true, 'vgp_due_date' => '2026-10-15']);
        $this->machineIn($this->rouen, ['reference' => 'NAC-0089'])->update(['is_subject_to_vgp' => true, 'vgp_due_date' => '2026-09-30']);
        $this->machineIn($this->rouen, ['reference' => 'NAC-0001'])->update(['is_subject_to_vgp' => true, 'vgp_due_date' => null]);
        $this->machineIn($this->rouen, ['reference' => 'NAC-0060'])->update(['is_subject_to_vgp' => true, 'vgp_due_date' => '2026-12-09']);

        $this->vgpWatch()
            ->assertSeeInOrder([
                'NAC-0001', __('dashboard.vgp.missing'),
                'NAC-0089', __('dashboard.vgp.expired', ['date' => '30/09/2026']),
                'NAC-0118', trans_choice('dashboard.vgp.expires', 5, ['date' => '15/10/2026']),
            ])
            ->assertDontSee('NAC-0060');
    }

    public function test_a_status_figure_opens_the_fleet_list_filtered_on_the_agency_and_status(): void
    {
        $this->fleetStatus()->assertSeeHtml('href="'.e(route('machines.index', ['agence' => $this->rouen->id, 'statut' => 'workshop'])).'"');
    }

    public function test_the_fleet_status_follows_a_machine_change_on_refresh(): void
    {
        $machine = $this->machineIn($this->rouen);
        $fleetStatus = $this->fleetStatus();

        $machine->update(['status' => MachineStatus::OutOfOrder]);
        $fleetStatus->dispatch('echo-private:fleet,.machine.changed');

        $this->assertSame(1, $fleetStatus->viewData('counts')['out_of_order']);
    }

    public function test_a_vgp_line_opens_the_vgp_page_of_the_machine(): void
    {
        $machine = Machine::factory()->for($this->rouen)->vgpExpired()->create();

        $this->vgpWatch()->assertSeeHtml('href="'.route('certification.machines.show', $machine).'"');
    }

    public function test_each_part_needs_the_permission_of_the_screen_it_opens(): void
    {
        $member = $this->memberOf($this->rouen, BookingPermission::ManageReservations);

        Livewire::actingAs($member)->test(FleetStatus::class, ['agencyId' => $this->rouen->id])->assertForbidden();
        Livewire::actingAs($member)->test(VgpWatch::class, ['agencyId' => $this->rouen->id])->assertForbidden();
        $this->actingAs($member)->get(route('dashboard'))->assertDontSee(__('dashboard.fleet.heading'))->assertDontSee(__('dashboard.vgp.heading'));

        $fleetOnly = $this->memberOf($this->rouen, FleetPermission::ManageMachines);
        $this->actingAs($fleetOnly)->get(route('dashboard'))->assertSee(__('dashboard.fleet.heading'))->assertDontSee(__('dashboard.vgp.heading'));
    }

    public function test_a_long_vgp_watch_shows_twenty_lines_and_a_link_to_the_vgp_machines(): void
    {
        Machine::factory()->for($this->rouen)->vgpExpired()->count(21)->create();

        $this->vgpWatch()
            ->assertSee(__('dashboard.section.more', ['shown' => 20, 'total' => 21]))
            ->assertSeeHtml('href="'.e(route('certification.machines', ['agence' => $this->rouen->id])).'"');
    }

    private function fleetStatus(): Testable
    {
        return Livewire::actingAs($this->employee)->test(FleetStatus::class, ['agencyId' => $this->rouen->id]);
    }

    private function vgpWatch(): Testable
    {
        return Livewire::actingAs($this->employee)->test(VgpWatch::class, ['agencyId' => $this->rouen->id]);
    }
}
