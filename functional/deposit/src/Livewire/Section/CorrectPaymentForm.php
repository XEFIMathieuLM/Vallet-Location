<?php

namespace Functional\Deposit\Livewire\Section;

use Flux\Flux;
use Functional\Booking\Models\Reservation;
use Functional\Deposit\Access\DepositPermission;
use Functional\Deposit\Actions\CorrectDepositPayment;
use Functional\Deposit\Enums\PaymentMethod;
use Functional\Deposit\Models\Deposit;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Inspection\Livewire\Concerns\ActsAsAgencyMember;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CorrectPaymentForm extends Component
{
    use ActsAsAgencyMember, DisplaysRefusals;

    public const NAME = 'deposit.correct-payment-form';

    private const MODAL = 'correct-deposit';

    #[Locked]
    public Reservation $reservation;

    public string $method = '';

    public string $reference = '';

    public string $reason = '';

    public function correct(CorrectDepositPayment $correctDepositPayment): void
    {
        Gate::authorize(DepositPermission::ManageDeposits->value);

        $this->validate(
            ['method' => ['required', Rule::enum(PaymentMethod::class)], 'reference' => ['nullable', 'string', 'max:255'], 'reason' => ['required', 'string', 'max:2000']],
            attributes: ['method' => __('deposit::section.method'), 'reference' => __('deposit::section.reference'), 'reason' => __('deposit::section.reason')],
        );

        $deposit = Deposit::query()->whereBelongsTo($this->reservation)->firstOrFail();
        $correctDepositPayment->handle($deposit, PaymentMethod::from($this->method), $this->reference, $this->reason, $this->agencyMember());

        Flux::modal(self::MODAL)->close();
        Flux::toast(text: __('deposit::section.corrected_toast'), variant: 'success');
        $this->reset('method', 'reference', 'reason');
        $this->dispatch('deposit-updated');
    }

    public function render(): View
    {
        return view('deposit::livewire.section.correct-payment-form', ['paymentMethods' => PaymentMethod::cases()]);
    }
}
