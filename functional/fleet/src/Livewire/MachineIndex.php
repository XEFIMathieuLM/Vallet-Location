<?php

namespace Functional\Fleet\Livewire;

use Flux\Flux;
use Functional\Fleet\Actions\ChangeMachineStatus;
use Functional\Fleet\Actions\RetireMachine;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Enums\MachineTransition;
use Functional\Fleet\Livewire\Concerns\DisplaysMachineBadges;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read LengthAwarePaginator<int, Machine> $machines
 * @property-read Machine|null $machineToRetire
 */
class MachineIndex extends Component
{
    use DisplaysMachineBadges, DisplaysRefusals, WithPagination;

    private const PER_PAGE = 50;

    #[Url(as: 'recherche')]
    public string $referenceSearch = '';

    #[Url(as: 'categorie')]
    public ?int $categoryId = null;

    #[Url(as: 'agence')]
    public ?int $agencyId = null;

    #[Url(as: 'statut')]
    public string $status = '';

    #[Locked]
    public ?int $machineToRetireId = null;

    public function updated(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Machine>
     */
    #[Computed]
    public function machines(): LengthAwarePaginator
    {
        return Machine::query()
            ->with(['category', 'agency'])
            ->when($this->referenceSearch !== '', fn ($query) => $query->whereLike('reference', "%{$this->referenceSearch}%"))
            ->when(MachineCategory::query()->find($this->categoryId), fn ($query, MachineCategory $category) => $query->whereBelongsTo($category, 'category'))
            ->when(Agency::query()->find($this->agencyId), fn ($query, Agency $agency) => $query->whereBelongsTo($agency))
            ->when(MachineStatus::tryFrom($this->status), fn ($query, MachineStatus $status) => $query->where('status', $status))
            ->orderBy('reference')
            ->paginate(self::PER_PAGE);
    }

    #[Computed]
    public function machineToRetire(): ?Machine
    {
        return $this->machineToRetireId === null ? null : Machine::query()->find($this->machineToRetireId);
    }

    public function applyTransition(int $machineId, string $transitionName, ChangeMachineStatus $changeMachineStatus): void
    {
        $machine = Machine::query()->findOrFail($machineId);
        $transition = MachineTransition::from($transitionName);

        if (! $transition->isManual() || $transition === MachineTransition::Retire) {
            throw new AuthorizationException;
        }

        $changeMachineStatus->handle($machine, $transition);
        Flux::toast(text: __('fleet::machines.index.status_changed', ['reference' => $machine->reference, 'status' => $machine->status->label()]), variant: 'success');
    }

    public function confirmRetirement(int $machineId): void
    {
        $this->machineToRetireId = $machineId;
        Flux::modal('retire-machine')->show();
    }

    public function retire(RetireMachine $retireMachine): void
    {
        $machine = $retireMachine->handle(Machine::query()->findOrFail($this->machineToRetireId));

        Flux::modal('retire-machine')->close();
        $this->machineToRetireId = null;
        Flux::toast(text: __('fleet::machines.index.status_changed', ['reference' => $machine->reference, 'status' => $machine->status->label()]), variant: 'success');
    }

    #[On('echo-private:fleet,.machine.changed')]
    #[On('echo-private:fleet,.fleet.imported')]
    public function refreshOnFleetChange(): void {}

    public function clearFilters(): void
    {
        $this->reset('referenceSearch', 'categoryId', 'agencyId', 'status');
    }

    public function render(): View
    {
        return view('fleet::livewire.machine-index', [
            'categories' => MachineCategory::query()->orderBy('name')->get(),
            'agencies' => Agency::query()->orderBy('name')->get(),
            'statuses' => MachineStatus::cases(),
            'machineBadges' => $this->badgesFor($this->machines->items()),
        ])->title(__('fleet::machines.index.title'));
    }
}
