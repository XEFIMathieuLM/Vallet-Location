<?php

namespace Functional\Fleet\Livewire;

use Functional\Fleet\Actions\ChangeMachineStatus;
use Functional\Fleet\Actions\RetireMachine;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Enums\MachineTransition;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read LengthAwarePaginator<int, Machine> $machines
 */
class MachineIndex extends Component
{
    use DisplaysRefusals, WithPagination;

    private const PER_PAGE = 50;

    #[Url(as: 'recherche')]
    public string $referenceSearch = '';

    #[Url(as: 'categorie')]
    public ?int $categoryId = null;

    #[Url(as: 'agence')]
    public ?int $agencyId = null;

    #[Url(as: 'statut')]
    public string $status = '';

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

    public function applyTransition(int $machineId, string $transitionName, ChangeMachineStatus $changeMachineStatus, RetireMachine $retireMachine): void
    {
        $machine = Machine::query()->findOrFail($machineId);
        $transition = MachineTransition::from($transitionName);

        if ($transition === MachineTransition::Retire) {
            $retireMachine->handle($machine);

            return;
        }

        if (! $transition->isManual()) {
            throw new AuthorizationException;
        }

        $changeMachineStatus->handle($machine, $transition);
    }

    #[On('echo-private:fleet,.machine.changed')]
    #[On('echo-private:fleet,.fleet.imported')]
    public function refreshOnFleetChange(): void {}

    public function render(): View
    {
        return view('fleet::livewire.machine-index', [
            'categories' => MachineCategory::query()->orderBy('name')->get(),
            'agencies' => Agency::query()->orderBy('name')->get(),
            'statuses' => MachineStatus::cases(),
        ])->title(__('fleet::machines.index.title'));
    }
}
