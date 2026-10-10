<?php

namespace Functional\Deposit\Livewire;

use Flux\Flux;
use Functional\Deposit\Access\DepositPermission;
use Functional\Deposit\Actions\SetDepositRate;
use Functional\Deposit\Livewire\Concerns\ValidatesDepositAmount;
use Functional\Deposit\Models\DepositRate;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Fleet\Models\MachineCategory;
use Functional\Inspection\Livewire\Concerns\ActsAsAgencyMember;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class DepositRatesIndex extends Component
{
    use ActsAsAgencyMember, DisplaysRefusals, ValidatesDepositAmount;

    public string $defaultAmount = '';

    public function saveDefault(SetDepositRate $setDepositRate): void
    {
        Gate::authorize(DepositPermission::ManageDepositRates->value);

        $setDepositRate->handle(null, $this->validatedAmount('defaultAmount'), $this->agencyMember());
        $this->reset('defaultAmount');
        Flux::toast(text: __('deposit::rates.saved'), variant: 'success');
    }

    public function render(): View
    {
        $rates = DepositRate::query()->get();
        $defaultRate = $rates->firstWhere('machine_category_id', null);

        return view('deposit::livewire.deposit-rates-index', [
            'defaultRate' => $defaultRate,
            'ratesByCategory' => $rates->whereNotNull('machine_category_id')->keyBy('machine_category_id'),
            'categories' => MachineCategory::query()->orderBy('name')->get(),
        ])->title(__('deposit::rates.title'));
    }
}
