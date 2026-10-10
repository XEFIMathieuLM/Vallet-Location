<?php

namespace Functional\Portal\Livewire\Staff;

use Flux\Flux;
use Functional\Certification\Livewire\Concerns\ActsAsAgencyMember;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Portal\Access\PortalPermission;
use Functional\Portal\Actions\RefuseReservationRequest;
use Functional\Portal\Models\ReservationRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class RefuseRequestModal extends Component
{
    use ActsAsAgencyMember, DisplaysRefusals;

    public const NAME = 'portal.refuse-request-modal';

    #[Locked]
    public int $reservationRequestId;

    public string $reason = '';

    public function mount(int $reservationRequestId): void
    {
        Gate::authorize(PortalPermission::HandleRequests->value);
        $this->reservationRequestId = $reservationRequestId;
    }

    public function refuse(RefuseReservationRequest $refuseReservationRequest): void
    {
        Gate::authorize(PortalPermission::HandleRequests->value);
        $this->reason = trim($this->reason);
        $this->validate(['reason' => ['required', 'string', 'max:'.config()->integer('portal.refusal_reason_max_length')]]);

        $refuseReservationRequest->handle(ReservationRequest::query()->findOrFail($this->reservationRequestId), $this->reason, $this->agencyMember());

        Flux::toast(text: __('portal::staff.requests.refused'), variant: 'success');
        $this->dispatch('reservation-request-decided');
    }

    public function render(): View
    {
        return view('portal::livewire.staff.refuse-request-modal', [
            'reservationRequest' => ReservationRequest::query()->with(['account', 'machine'])->findOrFail($this->reservationRequestId),
        ]);
    }
}
