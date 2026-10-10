<?php

namespace Functional\Deposit\Livewire\Section;

use Flux\Flux;
use Functional\Booking\Models\Reservation;
use Functional\Deposit\Access\DepositPermission;
use Functional\Deposit\Actions\CollectDeposit;
use Functional\Deposit\Enums\PaymentMethod;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Inspection\Livewire\Concerns\ActsAsAgencyMember;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CollectDepositForm extends Component
{
    use ActsAsAgencyMember, DisplaysRefusals;

    public const NAME = 'deposit.collect-deposit-form';

    private const MODAL = 'collect-deposit';

    #[Locked]
    public Reservation $reservation;

    public string $method = '';

    public string $reference = '';

    public function collect(CollectDeposit $collectDeposit): void
    {
        Gate::authorize(DepositPermission::ManageDeposits->value);

        $this->validate(
            ['method' => ['required', Rule::enum(PaymentMethod::class)], 'reference' => ['nullable', 'string', 'max:255']],
            attributes: ['method' => __('deposit::section.method'), 'reference' => __('deposit::section.reference')],
        );

        $collectDeposit->handle($this->reservation, PaymentMethod::from($this->method), $this->reference, $this->agencyMember());

        Flux::modal(self::MODAL)->close();
        Flux::toast(text: __('deposit::section.collected_toast'), variant: 'success');
        $this->reset('method', 'reference');
        $this->dispatch('deposit-updated');
    }

    public function render(): View
    {
        return view('deposit::livewire.section.collect-deposit-form', ['paymentMethods' => PaymentMethod::cases()]);
    }
}
