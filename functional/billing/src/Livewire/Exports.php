<?php

namespace Functional\Billing\Livewire;

use Flux\Flux;
use Functional\Billing\Actions\CreateBillingExport;
use Functional\Billing\Enums\BillingPermission;
use Functional\Billing\Models\BillingExport;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Exports extends Component
{
    use DisplaysRefusals;

    public function export(CreateBillingExport $createBillingExport): void
    {
        Gate::authorize(BillingPermission::Manage->value);

        $billingExport = $createBillingExport->handle(Auth::user() ?? abort(401));
        Flux::toast(text: trans_choice('billing::exports.created_toast', $billingExport->line_count, ['count' => $billingExport->line_count]), variant: 'success');
    }

    public function render(): View
    {
        return view('billing::livewire.exports', [
            'billingExports' => BillingExport::query()->with('creator')->latest('id')->get(),
        ])->title(__('billing::exports.title'));
    }
}
