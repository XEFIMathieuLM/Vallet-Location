<?php

namespace Functional\Deposit\Livewire\Section;

use Flux\Flux;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Deposit\Access\DepositPermission;
use Functional\Deposit\Actions\RefundDeposit;
use Functional\Deposit\Actions\SettleDeposit;
use Functional\Deposit\Models\Deposit;
use Functional\Deposit\Queries\BilledDamagesTotal;
use Functional\Deposit\ValueObjects\DepositRetention;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Inspection\Livewire\Concerns\ActsAsAgencyMember;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CloseDepositActions extends Component
{
    use ActsAsAgencyMember, DisplaysRefusals;

    public const NAME = 'deposit.close-deposit-actions';

    #[Locked]
    public Reservation $reservation;

    public bool $isNoDamageConfirmed = false;

    public function refund(RefundDeposit $refundDeposit): void
    {
        Gate::authorize(DepositPermission::ManageDeposits->value);

        $refundDeposit->handle($this->deposit(), $this->isNoDamageConfirmed, $this->agencyMember());
        $this->closed(__('deposit::section.refunded_toast'));
    }

    public function settle(SettleDeposit $settleDeposit): void
    {
        Gate::authorize(DepositPermission::ManageDeposits->value);

        $settleDeposit->handle($this->deposit(), $this->agencyMember());
        $this->closed(__('deposit::section.settled_toast'));
    }

    public function render(): View
    {
        $deposit = $this->deposit();

        return view('deposit::livewire.section.close-deposit-actions', [
            'deposit' => $deposit,
            'isAfterReturn' => $this->reservation->status === ReservationStatus::Closed,
            'retention' => DepositRetention::fromBilledTotal($deposit->amount, app(BilledDamagesTotal::class)->for($this->reservation)),
        ]);
    }

    private function deposit(): Deposit
    {
        return Deposit::query()->whereBelongsTo($this->reservation)->firstOrFail();
    }

    private function closed(string $confirmation): void
    {
        $this->isNoDamageConfirmed = false;
        Flux::toast(text: $confirmation, variant: 'success');
        $this->dispatch('deposit-updated');
    }
}
