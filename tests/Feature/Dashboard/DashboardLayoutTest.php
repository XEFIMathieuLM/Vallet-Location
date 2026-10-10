<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Fleet\Models\Agency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\InspectsPrimaryActions;
use Tests\Feature\Dashboard\Concerns\BuildsDashboardFixtures;
use Tests\TestCase;

class DashboardLayoutTest extends TestCase
{
    use BuildsDashboardFixtures, InspectsPrimaryActions, RefreshDatabase;

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

    public function test_the_welcome_band_sums_up_the_operations_of_the_day(): void
    {
        foreach (['DEP-1', 'DEP-2', 'DEP-3'] as $reference) {
            $this->reservationOf($this->machineIn($this->rouen, ['reference' => $reference]), ReservationStatus::Confirmed, '2026-10-10', '2026-10-12');
        }
        $this->reservationOf($this->machineIn($this->rouen), ReservationStatus::InProgress, '2026-10-05', '2026-10-10');
        $this->reservationOf($this->machineIn($this->rouen), ReservationStatus::InProgress, '2026-10-01', '2026-10-08');
        $this->reservationOf($this->machineIn($this->rouen), ReservationStatus::InProgress, '2026-10-01', '2026-10-09');

        $this->dashboard()
            ->assertSee(__('dashboard.welcome.title'))
            ->assertSee('3 départs, 1 retour et 2 retours en retard aujourd\'hui.');
    }

    public function test_the_upcoming_departures_follow_the_configured_number_of_days(): void
    {
        $departure = $this->reservationOf($this->machineIn($this->rouen, ['reference' => 'NAC-14']), ReservationStatus::Confirmed, '2026-10-14', '2026-10-16');

        $this->dashboard()
            ->assertSeeInOrder(array_map(fn (int $day): string => 'data-upcoming-day="2026-10-'.$day.'"', range(11, 17)), escape: false)
            ->assertDontSee('data-upcoming-day="2026-10-18"', escape: false)
            ->assertSee('href="'.route('reservations.show', $departure).'"', escape: false)
            ->assertSee(__('dashboard.upcoming.no_departure'));

        config(['dashboard.upcoming_departure_days' => 3]);

        $this->dashboard()
            ->assertSee('data-upcoming-day="2026-10-13"', escape: false)
            ->assertDontSee('data-upcoming-day="2026-10-14"', escape: false);
    }

    public function test_the_upcoming_departures_link_to_every_reservation_beyond_the_limit(): void
    {
        config(['dashboard.section_limit' => 1]);
        $this->reservationOf($this->machineIn($this->rouen), ReservationStatus::Confirmed, '2026-10-12', '2026-10-13');
        $this->reservationOf($this->machineIn($this->rouen), ReservationStatus::Confirmed, '2026-10-13', '2026-10-14');

        $this->dashboard()->assertSee(__('dashboard.operations.see_all'));
    }

    public function test_only_late_returns_turn_their_tile_yellow(): void
    {
        $this->dashboard()->assertDontSee('data-tile="accent"', escape: false);

        $this->reservationOf($this->machineIn($this->rouen), ReservationStatus::InProgress, '2026-10-01', '2026-10-08');

        $this->dashboard()->assertSee('data-tile="accent"', escape: false);
    }

    public function test_the_agency_choice_lives_in_the_top_bar_and_no_button_competes_with_it(): void
    {
        $response = $this->dashboard();

        $response->assertSee('x-teleport="#top-bar-actions"', escape: false)
            ->assertSee('wire:model.live="agency"', escape: false);
        $this->assertAtMostOnePrimaryAction((string) $response->getContent());
    }

    private function dashboard(): TestResponse
    {
        return $this->actingAs($this->employee)->get(route('dashboard'))->assertOk();
    }
}
