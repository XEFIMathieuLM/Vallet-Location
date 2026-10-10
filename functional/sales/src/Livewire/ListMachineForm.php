<?php

namespace Functional\Sales\Livewire;

use Functional\Billing\Money\Money;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Fleet\Models\Machine;
use Functional\Inspection\Livewire\Concerns\ActsAsAgencyMember;
use Functional\Sales\Access\SalesPermission;
use Functional\Sales\Actions\ListMachineForSale;
use Functional\Sales\Livewire\Concerns\EditsSaleListing;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * @property-read Collection<int, Machine> $machines
 */
class ListMachineForm extends Component
{
    use ActsAsAgencyMember, DisplaysRefusals, EditsSaleListing;

    private const MACHINE_SEARCH_LIMIT = 20;

    #[Url(as: 'machine')]
    public ?int $machineId = null;

    public string $machineSearch = '';

    /**
     * @return Collection<int, Machine>
     */
    #[Computed]
    public function machines(): Collection
    {
        return Machine::query()
            ->with('category')
            ->when($this->machineSearch !== '', fn ($query) => $query->whereLike('reference', "%{$this->machineSearch}%"))
            ->when($this->machineSearch === '' && $this->machineId !== null, fn ($query) => $query->whereKey($this->machineId))
            ->orderBy('reference')
            ->limit(self::MACHINE_SEARCH_LIMIT)
            ->get();
    }

    public function save(ListMachineForSale $listMachineForSale): void
    {
        Gate::authorize(SalesPermission::Manage->value);
        $this->validate(['machineId' => ['required', 'exists:machines,id'], ...$this->listingRules()], attributes: [
            'machineId' => __('sales::sales.fields.machine'),
            ...$this->listingAttributes(),
        ]);

        $sale = $listMachineForSale->handle($this->agencyMember(), Machine::query()->findOrFail($this->machineId), $this->listing(Money::fromInput($this->askingPrice)));

        session()->flash('sale-flash', __('sales::sales.form.listed', ['reference' => $sale->machine->reference]));
        $this->redirectRoute('sales.show', $sale, navigate: true);
    }

    public function render(): View
    {
        return view('sales::livewire.list-machine-form')->title(__('sales::sales.form.title'));
    }
}
