<?php

namespace Functional\Deposit\Livewire;

use Flux\Flux;
use Functional\Deposit\Access\DepositPermission;
use Functional\Deposit\Actions\RemoveDepositRate;
use Functional\Deposit\Actions\ResolveDepositAmount;
use Functional\Deposit\Actions\SetDepositRate;
use Functional\Deposit\Livewire\Concerns\ValidatesDepositAmount;
use Functional\Deposit\Models\DepositRate;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Fleet\Models\MachineCategory;
use Functional\Inspection\Livewire\Concerns\ActsAsAgencyMember;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class DepositRateForm extends Component
{
    use ActsAsAgencyMember, DisplaysRefusals, ValidatesDepositAmount;

    #[Locked]
    public MachineCategory $category;

    public string $amount = '';

    public function save(SetDepositRate $setDepositRate): void
    {
        Gate::authorize(DepositPermission::ManageDepositRates->value);

        $setDepositRate->handle($this->category, $this->validatedAmount('amount'), $this->agencyMember());
        $this->reset('amount');
        Flux::toast(text: __('deposit::rates.saved'), variant: 'success');
    }

    public function remove(RemoveDepositRate $removeDepositRate): void
    {
        Gate::authorize(DepositPermission::ManageDepositRates->value);

        $removeDepositRate->handle($this->category);
        Flux::toast(text: __('deposit::rates.removed'), variant: 'success');
    }

    public function render(ResolveDepositAmount $resolveDepositAmount): View
    {
        return view('deposit::livewire.deposit-rate-form', [
            'effectiveAmount' => $resolveDepositAmount->forCategory($this->category),
            'hasOwnRate' => DepositRate::query()->where('machine_category_id', $this->category->id)->exists(),
        ])->title(__('deposit::rates.category_title', ['category' => $this->category->name]));
    }
}
