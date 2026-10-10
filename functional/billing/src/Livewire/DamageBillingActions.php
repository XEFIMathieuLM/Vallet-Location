<?php

namespace Functional\Billing\Livewire;

use Flux\Flux;
use Functional\Billing\Actions\BillDamage;
use Functional\Billing\Actions\WaiveDamage;
use Functional\Billing\Enums\BillingPermission;
use Functional\Billing\Livewire\Concerns\DisplaysBillingRefusals;
use Functional\Billing\Money\Money;
use Functional\Inspection\Models\Damage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class DamageBillingActions extends Component
{
    use DisplaysBillingRefusals;

    #[Locked]
    public Damage $damage;

    public string $amount = '';

    public string $label = '';

    public string $waiverReason = '';

    public function bill(BillDamage $billDamage): void
    {
        Gate::authorize(BillingPermission::Manage->value);

        $this->validate([
            'amount' => ['required', 'regex:'.Money::INPUT_PATTERN, 'not_regex:/^0+([.,]0+)?$/'],
            'label' => ['required', 'string', 'max:255'],
        ], ['amount.regex' => __('billing::damages.amount_format'), 'amount.not_regex' => __('billing::damages.refusals.amount_required')], [
            'amount' => __('billing::damages.amount'),
            'label' => __('billing::damages.label'),
        ]);

        $billDamage->handle($this->damage, Money::fromInput($this->amount), $this->label, Auth::user() ?? abort(401));
        $this->settled(__('billing::damages.billed_toast'));
    }

    public function waive(WaiveDamage $waiveDamage): void
    {
        Gate::authorize(BillingPermission::Manage->value);

        $this->validate(['waiverReason' => ['required', 'string', 'max:2000']], attributes: ['waiverReason' => __('billing::damages.waiver_reason')]);

        $waiveDamage->handle($this->damage, $this->waiverReason, Auth::user() ?? abort(401));
        $this->settled(__('billing::damages.waived_toast'));
    }

    public function render(): View
    {
        return view('billing::livewire.damage-billing-actions');
    }

    private function settled(string $successMessage): void
    {
        Flux::toast(text: $successMessage, variant: 'success');
        $this->reset('amount', 'label', 'waiverReason');
        $this->js('$wire.$parent.$refresh()');
    }
}
