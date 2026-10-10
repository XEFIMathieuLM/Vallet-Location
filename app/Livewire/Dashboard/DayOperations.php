<?php

namespace App\Livewire\Dashboard;

use App\Dashboard\DashboardSection;
use Carbon\CarbonImmutable;
use Functional\Booking\Access\BookingPermission;
use Functional\Booking\Models\Reservation;
use Functional\Booking\Queries\DayOperations as DayOperationsQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class DayOperations extends Component
{
    #[Reactive]
    public ?int $agencyId = null;

    public function mount(): void
    {
        Gate::authorize(BookingPermission::ManageReservations->value);
    }

    #[On('echo-private:fleet,.reservation.changed')]
    #[On('echo-private:fleet,.machine.changed')]
    public function refreshOnFleetChange(): void {}

    public function render(DayOperationsQuery $dayOperations): View
    {
        $today = CarbonImmutable::today();
        $upcomingDays = config()->integer('dashboard.upcoming_departure_days');

        return view('livewire.dashboard.day-operations', [
            'today' => $today,
            'upcomingDays' => $upcomingDays,
            'isAllAgencies' => $this->agencyId === null,
            'departures' => $this->section($dayOperations->departures($this->agencyId, $today)),
            'upcomingDepartures' => $this->section($dayOperations->upcomingDepartures($this->agencyId, $today, $upcomingDays)),
            'returns' => $this->section($dayOperations->returns($this->agencyId, $today)),
        ]);
    }

    /**
     * @param  Builder<Reservation>  $reservations
     */
    private function section(Builder $reservations): DashboardSection
    {
        return DashboardSection::fromQuery($reservations, config()->integer('dashboard.section_limit'));
    }
}
