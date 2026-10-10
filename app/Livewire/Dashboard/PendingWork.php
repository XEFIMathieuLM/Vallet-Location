<?php

namespace App\Livewire\Dashboard;

use App\Dashboard\PendingWorkCounters;
use Functional\Sales\Access\SalesPermission;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class PendingWork extends Component
{
    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        $listeners = [
            'echo-private:fleet,.certificate.changed' => '$refresh',
            'echo-private:fleet,.deposit.changed' => '$refresh',
            'echo-private:fleet,.damage.changed' => '$refresh',
            'echo-private:fleet,.reservation.changed' => '$refresh',
        ];

        if (Gate::allows(SalesPermission::Manage->value)) {
            $listeners['echo-private:sales,.sale.changed'] = '$refresh';
        }

        return $listeners;
    }

    public function render(PendingWorkCounters $pendingWorkCounters): View
    {
        /** @var Authorizable $employee */
        $employee = Auth::user();

        return view('livewire.dashboard.pending-work', [
            'counters' => $pendingWorkCounters->forUser($employee),
        ]);
    }
}
