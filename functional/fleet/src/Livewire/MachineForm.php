<?php

namespace Functional\Fleet\Livewire;

use Carbon\CarbonImmutable;
use Functional\Fleet\Actions\CreateMachine;
use Functional\Fleet\Actions\UpdateMachine;
use Functional\Fleet\Data\MachineAttributes;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class MachineForm extends Component
{
    use DisplaysRefusals;

    #[Locked]
    public ?int $machineId = null;

    public string $reference = '';

    public ?int $categoryId = null;

    public ?int $agencyId = null;

    public bool $isSubjectToVgp = false;

    public string $vgpDueDate = '';

    public function mount(?Machine $machine = null): void
    {
        if ($machine === null || ! $machine->exists) {
            return;
        }

        $this->machineId = $machine->id;
        $this->reference = $machine->reference;
        $this->categoryId = $machine->machine_category_id;
        $this->agencyId = $machine->agency_id;
        $this->isSubjectToVgp = $machine->is_subject_to_vgp;
        $this->vgpDueDate = $machine->vgp_due_date?->toDateString() ?? '';
    }

    public function save(CreateMachine $createMachine, UpdateMachine $updateMachine): void
    {
        $this->validate();

        $attributes = new MachineAttributes(
            reference: $this->reference,
            categoryId: (int) $this->categoryId,
            agencyId: (int) $this->agencyId,
            isSubjectToVgp: $this->isSubjectToVgp,
            vgpDueDate: $this->vgpDueDate !== '' ? CarbonImmutable::parse($this->vgpDueDate) : null,
        );

        $machine = $this->machineId === null
            ? $createMachine->handle($attributes)
            : $updateMachine->handle(Machine::query()->findOrFail($this->machineId), $attributes);

        session()->flash('machine-saved', __('fleet::machines.form.saved', ['reference' => $machine->reference]));
        $this->redirectRoute('machines.index', navigate: true);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:50'],
            'categoryId' => ['required', 'exists:machine_categories,id'],
            'agencyId' => ['required', 'exists:agencies,id'],
            'isSubjectToVgp' => ['boolean'],
            'vgpDueDate' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'reference' => __('fleet::machines.fields.reference'),
            'categoryId' => __('fleet::machines.fields.category'),
            'agencyId' => __('fleet::machines.fields.agency'),
            'vgpDueDate' => __('fleet::machines.fields.vgp_due_date'),
        ];
    }

    public function render(): View
    {
        $title = $this->machineId === null ? __('fleet::machines.form.create_title') : __('fleet::machines.form.edit_title');

        return view('fleet::livewire.machine-form', [
            'title' => $title,
            'categories' => MachineCategory::query()->orderBy('name')->get(),
            'agencies' => Agency::query()->orderBy('name')->get(),
        ])->title($title);
    }
}
