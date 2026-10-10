<?php

namespace Tests\Feature\Dashboard;

use App\Livewire\Dashboard\DayOperations;
use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ConflictReason;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Fleet\Models\Agency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Feature\Dashboard\Concerns\BuildsDashboardFixtures;
use Tests\TestCase;

class AnomaliesTest extends TestCase
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

    public function test_a_late_return_is_listed_with_its_days_of_delay(): void
    {
        $late = $this->reservationOf($this->machineIn($this->rouen, ['reference' => 'ECH-0040']), ReservationStatus::InProgress, '2026-10-01', '2026-10-07');

        $this->dayOperations()
            ->assertSeeInOrder([__('dashboard.operations.late_returns'), 'ECH-0040', $late->customer->name, '01/10/2026 → 07/10/2026'])
            ->assertSee(trans_choice('dashboard.operations.days_late', 3));
    }

    public function test_a_reservation_in_conflict_is_listed_with_its_reason(): void
    {
        $this->reservationOf($this->machineIn($this->rouen, ['reference' => 'MINI-0007']), ReservationStatus::Confirmed, '2026-10-15', '2026-10-17', ['conflict_reason' => ConflictReason::MachineUnavailable]);

        $this->dayOperations()
            ->assertSeeInOrder([__('dashboard.operations.conflicts'), 'MINI-0007', ConflictReason::MachineUnavailable->label()]);
    }

    public function test_a_cancelled_reservation_leaves_the_conflicts_on_refresh(): void
    {
        $conflict = $this->reservationOf($this->machineIn($this->rouen, ['reference' => 'MINI-0007']), ReservationStatus::Confirmed, '2026-10-15', '2026-10-17', ['conflict_reason' => ConflictReason::VgpExpired]);
        $dayOperations = $this->dayOperations()->assertSee('MINI-0007');

        $conflict->update(['status' => ReservationStatus::Cancelled, 'conflict_reason' => null]);

        $dayOperations->dispatch('echo-private:fleet,.reservation.changed')->assertDontSee('MINI-0007');
    }

    public function test_without_anomaly_both_sections_say_so(): void
    {
        $this->dayOperations()
            ->assertSee(__('dashboard.operations.empty.late_returns'))
            ->assertSee(__('dashboard.operations.empty.conflicts'));
    }

    public function test_a_return_planned_today_is_not_late(): void
    {
        $this->reservationOf($this->machineIn($this->rouen, ['reference' => 'ECH-0041']), ReservationStatus::InProgress, '2026-10-01', '2026-10-10');

        $this->dayOperations()
            ->assertSeeInOrder([__('dashboard.operations.returns'), 'ECH-0041', __('dashboard.operations.late_returns'), __('dashboard.operations.empty.late_returns')]);
    }

    public function test_late_returns_are_sorted_oldest_first(): void
    {
        $this->reservationOf($this->machineIn($this->rouen, ['reference' => 'ECH-0002']), ReservationStatus::InProgress, '2026-10-01', '2026-10-09');
        $this->reservationOf($this->machineIn($this->rouen, ['reference' => 'ECH-0001']), ReservationStatus::InProgress, '2026-09-20', '2026-10-02');

        $this->dayOperations()->assertSeeInOrder([__('dashboard.operations.late_returns'), 'ECH-0001', 'ECH-0002']);
    }

    private function dayOperations(): Testable
    {
        return Livewire::actingAs($this->employee)->test(DayOperations::class, ['agencyId' => $this->rouen->id]);
    }
}
