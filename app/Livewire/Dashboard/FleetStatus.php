<?php

namespace App\Livewire\Dashboard;

use Functional\Fleet\Access\FleetPermission;
use Functional\Fleet\Queries\FleetStatusCounts;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class FleetStatus extends Component
{
    #[Reactive]
    public ?int $agencyId = null;

    public function mount(): void
    {
        Gate::authorize(FleetPermission::ManageMachines->value);
    }

    #[On('echo-private:fleet,.machine.changed')]
    #[On('echo-private:fleet,.reservation.changed')]
    #[On('echo-private:fleet,.fleet.imported')]
    public function refreshOnFleetChange(): void {}

    public function render(FleetStatusCounts $fleetStatusCounts): View
    {
        return view('livewire.dashboard.fleet-status', [
            'counts' => $fleetStatusCounts->count($this->agencyId),
        ]);
    }
}
