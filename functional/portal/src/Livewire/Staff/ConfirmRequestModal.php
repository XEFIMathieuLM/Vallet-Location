<?php

namespace Functional\Portal\Livewire\Staff;

use Flux\Flux;
use Functional\Booking\Queries\AvailableMachinesQuery;
use Functional\Certification\Livewire\Concerns\ActsAsAgencyMember;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Fleet\Models\Machine;
use Functional\Portal\Access\PortalPermission;
use Functional\Portal\Actions\ConfirmReservationRequest;
use Functional\Portal\Data\CustomerChoice;
use Functional\Portal\Enums\CustomerChoiceKind;
use Functional\Portal\Models\ReservationRequest;
use Functional\Portal\Queries\SuggestedCustomers;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ConfirmRequestModal extends Component
{
    use ActsAsAgencyMember, DisplaysRefusals;

    public const NAME = 'portal.confirm-request-modal';

    #[Locked]
    public int $reservationRequestId;

    public ?int $machineId = null;

    public string $customerChoice = 'create';

    public ?int $existingCustomerId = null;

    public string $customerSearch = '';

    public function mount(int $reservationRequestId): void
    {
        Gate::authorize(PortalPermission::HandleRequests->value);
        $this->reservationRequestId = $reservationRequestId;
        $this->machineId = $this->reservationRequest()->machine_id;
    }

    public function confirm(ConfirmReservationRequest $confirmReservationRequest): void
    {
        Gate::authorize(PortalPermission::HandleRequests->value);
        $this->validate([
            'machineId' => ['required', 'integer'],
            'customerChoice' => ['required', 'in:'.CustomerChoiceKind::Existing->value.','.CustomerChoiceKind::Create->value],
            'existingCustomerId' => ['nullable', 'required_if:customerChoice,'.CustomerChoiceKind::Existing->value, 'integer'],
        ]);

        $customerChoice = $this->customerChoice === CustomerChoiceKind::Existing->value && $this->existingCustomerId !== null
            ? CustomerChoice::existing($this->existingCustomerId)
            : CustomerChoice::create();
        $confirmReservationRequest->handle($this->reservationRequest(), Machine::query()->findOrFail($this->machineId), $customerChoice, $this->agencyMember());

        Flux::toast(text: __('portal::staff.requests.confirmed'), variant: 'success');
        $this->dispatch('reservation-request-decided');
    }

    public function render(SuggestedCustomers $suggestedCustomers): View
    {
        $reservationRequest = $this->reservationRequest();

        return view('portal::livewire.staff.confirm-request-modal', [
            'reservationRequest' => $reservationRequest,
            'machines' => $this->candidateMachines($reservationRequest),
            'suggestedCustomers' => $reservationRequest->account->isAttached() ? new Collection : $suggestedCustomers->for($reservationRequest->account),
            'searchedCustomers' => $suggestedCustomers->search($this->customerSearch),
        ]);
    }

    /**
     * @return Collection<int, Machine>
     */
    private function candidateMachines(ReservationRequest $reservationRequest): Collection
    {
        $availableMachines = app(AvailableMachinesQuery::class)->get($reservationRequest->start_date, $reservationRequest->end_date, $reservationRequest->machine->category);

        return $availableMachines->sortBy(fn (Machine $machine): int => $machine->id === $reservationRequest->machine_id ? 0 : 1)->values();
    }

    private function reservationRequest(): ReservationRequest
    {
        return ReservationRequest::query()->with(['account.customer', 'machine.category', 'machine.agency'])->findOrFail($this->reservationRequestId);
    }
}
