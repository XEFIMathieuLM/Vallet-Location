<?php

namespace Functional\Billing\Livewire;

use Functional\Billing\Actions\BillDamage;
use Functional\Billing\Actions\WaiveDamage;
use Functional\Billing\Support\EuroAmount;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Inspection\Models\Damage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class DamageBillingActions extends Component
{
    use DisplaysRefusals;

    #[Locked]
    public Damage $damage;

    public string $amount = '';

    public string $label = '';

    public string $waiverReason = '';

    public function bill(BillDamage $billDamage, EuroAmount $euroAmount): void
    {
        Gate::authorize('billing.manage');

        $this->validate([
            'amount' => ['required', 'regex:'.EuroAmount::INPUT_PATTERN, 'not_regex:/^0+([.,]0+)?$/'],
            'label' => ['required', 'string', 'max:255'],
        ], ['amount.regex' => __('billing::damages.amount_format'), 'amount.not_regex' => __('billing::damages.refusals.amount_required')], [
            'amount' => __('billing::damages.amount'),
            'label' => __('billing::damages.label'),
        ]);

        $billDamage->handle($this->damage, $euroAmount->toCents($this->amount), $this->label, Auth::user() ?? abort(401));
        $this->settled();
    }

    public function waive(WaiveDamage $waiveDamage): void
    {
        Gate::authorize('billing.manage');

        $this->validate(['waiverReason' => ['required', 'string', 'max:2000']], attributes: ['waiverReason' => __('billing::damages.waiver_reason')]);

        $waiveDamage->handle($this->damage, $this->waiverReason, Auth::user() ?? abort(401));
        $this->settled();
    }

    public function render(): View
    {
        return view('billing::livewire.damage-billing-actions');
    }

    private function settled(): void
    {
        $this->reset('amount', 'label', 'waiverReason');
        $this->js('$wire.$parent.$refresh()');
    }
}
