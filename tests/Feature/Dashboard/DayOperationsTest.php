<?php

namespace Tests\Feature\Dashboard;

use App\Livewire\Dashboard\DayOperations;
use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Feature\Dashboard\Concerns\BuildsDashboardFixtures;
use Tests\TestCase;

class DayOperationsTest extends TestCase
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

    public function test_the_departures_of_the_day_are_listed_with_machine_customer_and_dates(): void
    {
        $first = $this->reservationOf($this->machineIn($this->rouen, ['reference' => 'NAC-0001']), ReservationStatus::Confirmed, '2026-10-10', '2026-10-14');
        $second = $this->reservationOf($this->machineIn($this->rouen, ['reference' => 'MINI-0002']), ReservationStatus::Confirmed, '2026-10-10', '2026-10-12');

        $this->dayOperations()
            ->assertSeeInOrder([__('dashboard.operations.departures'), 'MINI-0002', 'NAC-0001', __('dashboard.operations.upcoming_departures', ['days' => 7])])
            ->assertSee($first->machine->category->name)
            ->assertSee($first->customer->name)
            ->assertSee($second->customer->name)
            ->assertSee('10/10/2026 → 14/10/2026');
    }

    public function test_the_returns_of_the_day_are_listed(): void
    {
        $returning = $this->reservationOf($this->machineIn($this->rouen, ['reference' => 'ECH-0040']), ReservationStatus::InProgress, '2026-10-05', '2026-10-10');

        $this->dayOperations()
            ->assertSeeInOrder([__('dashboard.operations.returns'), 'ECH-0040', $returning->customer->name]);
    }

    public function test_a_line_opens_the_reservation_detail(): void
    {
        $reservation = $this->reservationOf($this->machineIn($this->rouen), ReservationStatus::Confirmed, '2026-10-10', '2026-10-14');

        $this->dayOperations()->assertSeeHtml('href="'.route('reservations.show', $reservation).'"');
    }

    public function test_a_missed_departure_stays_in_the_departures_with_its_planned_date(): void
    {
        $this->reservationOf($this->machineIn($this->rouen, ['reference' => 'COMP-0021']), ReservationStatus::Confirmed, '2026-10-08', '2026-10-14');

        $this->dayOperations()
            ->assertSee('COMP-0021')
            ->assertSee(__('dashboard.operations.planned_on', ['date' => '08/10/2026']));
    }

    public function test_empty_sections_say_there_is_nothing_planned(): void
    {
        $this->dayOperations()
            ->assertSee(__('dashboard.operations.empty.departures'))
            ->assertSee(__('dashboard.operations.empty.returns'))
            ->assertSee(__('dashboard.operations.empty.upcoming_departures', ['days' => 7]));
    }

    public function test_upcoming_departures_of_the_next_seven_days_are_grouped_by_date(): void
    {
        $this->reservationOf($this->machineIn($this->rouen, ['reference' => 'NAC-0118']), ReservationStatus::Confirmed, '2026-10-13', '2026-10-15');
        $this->reservationOf($this->machineIn($this->rouen, ['reference' => 'NAC-0999']), ReservationStatus::Confirmed, '2026-10-18', '2026-10-20');

        $this->dayOperations()
            ->assertSeeInOrder([__('dashboard.operations.upcoming_departures', ['days' => 7]), 'mardi 13 octobre', 'NAC-0118'])
            ->assertDontSee('NAC-0999');
    }

    public function test_a_departure_recorded_elsewhere_leaves_the_list_on_refresh(): void
    {
        $reservation = $this->reservationOf($this->machineIn($this->rouen, ['reference' => 'NAC-0042']), ReservationStatus::Confirmed, '2026-10-10', '2026-10-14');
        $dayOperations = $this->dayOperations()->assertSee('NAC-0042');

        $reservation->update(['status' => ReservationStatus::InProgress, 'departed_at' => CarbonImmutable::now()]);

        $dayOperations->dispatch('echo-private:fleet,.reservation.changed')->assertDontSee('NAC-0042');
    }

    public function test_the_reservations_of_another_agency_are_not_listed(): void
    {
        $this->reservationOf($this->machineIn($this->agencyNamed('Évreux'), ['reference' => 'NAC-0777']), ReservationStatus::Confirmed, '2026-10-10', '2026-10-14');

        $this->dayOperations()->assertDontSee('NAC-0777');
    }

    public function test_an_employee_without_reservation_access_cannot_see_the_operations(): void
    {
        $member = $this->memberOf($this->rouen);

        Livewire::actingAs($member)->test(DayOperations::class, ['agencyId' => $this->rouen->id])->assertForbidden();
        $this->actingAs($member)->get(route('dashboard'))->assertOk()->assertDontSee(__('dashboard.operations.departures'));
    }

    public function test_a_long_section_shows_twenty_lines_the_total_and_a_link_to_the_reservations(): void
    {
        Machine::factory()->for($this->rouen)->count(21)->create()
            ->each(fn (Machine $machine) => $this->reservationOf($machine, ReservationStatus::Confirmed, '2026-10-10', '2026-10-12'));

        $this->dayOperations()
            ->assertSee(__('dashboard.section.more', ['shown' => 20, 'total' => 21]))
            ->assertSeeHtml('href="'.e(route('reservations.index', ['statut' => 'confirmed'])).'"');
    }

    private function dayOperations(): Testable
    {
        return Livewire::actingAs($this->employee)->test(DayOperations::class, ['agencyId' => $this->rouen->id]);
    }
}
