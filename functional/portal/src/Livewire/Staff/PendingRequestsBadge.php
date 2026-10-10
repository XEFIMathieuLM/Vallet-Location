<?php

namespace Functional\Portal\Livewire\Staff;

use Functional\Portal\Queries\PendingRequests;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PendingRequestsBadge extends Component
{
    public const NAME = 'portal.pending-requests-badge';

    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return ['echo-private:portal-requests,.reservation-request.changed' => '$refresh'];
    }

    public function render(PendingRequests $pendingRequests): View
    {
        return view('portal::livewire.staff.pending-requests-badge', ['pendingCount' => $pendingRequests->count()]);
    }
}
