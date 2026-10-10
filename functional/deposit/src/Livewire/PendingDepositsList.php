<?php

namespace Functional\Deposit\Livewire;

use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Queries\PendingDeposits;
use Functional\Fleet\Models\Agency;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class PendingDepositsList extends Component
{
    #[Url(as: 'agence')]
    public ?int $agencyId = null;

    #[Url(as: 'etat')]
    public string $status = '';

    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return ['echo-private:fleet,.deposit.changed' => '$refresh'];
    }

    public function render(PendingDeposits $pendingDeposits): View
    {
        return view('deposit::livewire.pending-deposits-list', [
            'deposits' => $pendingDeposits->query($this->agencyId ?: null, DepositStatus::tryFrom($this->status))->get(),
            'pendingDeposits' => $pendingDeposits,
            'agencies' => Agency::query()->orderBy('name')->get(),
            'awaitingStatuses' => array_filter(DepositStatus::cases(), fn (DepositStatus $depositStatus): bool => $depositStatus->isAwaitingAction()),
        ])->title(__('deposit::pending.title'));
    }
}
