<?php

namespace Functional\Portal\Livewire\Staff;

use Functional\Fleet\Models\Agency;
use Functional\Portal\Access\PortalPermission;
use Functional\Portal\Pricing\IndicativePriceFormatter;
use Functional\Portal\Queries\PendingRequests;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class OnlineRequests extends Component
{
    use WithPagination;

    #[Url(as: 'agence')]
    public ?int $agencyId = null;

    public ?int $confirmingRequestId = null;

    public ?int $refusingRequestId = null;

    public function mount(): void
    {
        Gate::authorize(PortalPermission::HandleRequests->value);
    }

    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return [
            'echo-private:portal-requests,.reservation-request.changed' => '$refresh',
            'echo-private:fleet,.reservation.changed' => '$refresh',
        ];
    }

    public function updatedAgencyId(): void
    {
        $this->resetPage();
    }

    public function startConfirming(int $reservationRequestId): void
    {
        $this->confirmingRequestId = $reservationRequestId;
        $this->modal('confirm-request')->show();
    }

    public function startRefusing(int $reservationRequestId): void
    {
        $this->refusingRequestId = $reservationRequestId;
        $this->modal('refuse-request')->show();
    }

    #[On('reservation-request-decided')]
    public function closeDecision(): void
    {
        $this->modal('confirm-request')->close();
        $this->modal('refuse-request')->close();
        $this->reset('confirmingRequestId', 'refusingRequestId');
    }

    public function render(PendingRequests $pendingRequests, IndicativePriceFormatter $priceFormatter): View
    {
        return view('portal::livewire.staff.online-requests', [
            'reservationRequests' => $pendingRequests->paginate($this->agencyId),
            'agencies' => Agency::query()->orderBy('name')->get(),
            'priceFormatter' => $priceFormatter,
        ])->title(__('portal::staff.requests.title'));
    }
}
