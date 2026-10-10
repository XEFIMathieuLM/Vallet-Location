<?php

namespace App\Livewire\Dashboard;

use App\Models\User;
use BackedEnum;
use Functional\Accounts\Access\AccountsPermission;
use Functional\Billing\Enums\BillingPermission;
use Functional\Booking\Access\BookingPermission;
use Functional\Certification\Enums\CertificationPermission;
use Functional\Deposit\Access\DepositPermission;
use Functional\Fleet\Access\FleetPermission;
use Functional\Fleet\Models\Agency;
use Functional\Inspection\Access\InspectionPermission;
use Functional\Sales\Access\SalesPermission;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class Dashboard extends Component
{
    public const ALL_AGENCIES = 'toutes';

    #[Url(as: 'agence')]
    public string $agency = '';

    #[Computed]
    public function agencyId(): ?int
    {
        if ($this->agency === self::ALL_AGENCIES) {
            return null;
        }

        $selectedAgencyId = ctype_digit($this->agency) ? Agency::query()->whereKey((int) $this->agency)->value('id') : null;

        return $selectedAgencyId ?? $this->ownAgencyId();
    }

    #[Computed]
    public function ownAgencyId(): int
    {
        return $this->employee()->agencyId();
    }

    /**
     * @return Collection<int, Agency>
     */
    #[Computed]
    public function agencies(): Collection
    {
        return Agency::query()->orderBy('name')->get();
    }

    #[Computed]
    public function hasAnySection(): bool
    {
        return Gate::any(array_map(fn (BackedEnum $permission): string => (string) $permission->value, [
            BookingPermission::ManageReservations,
            FleetPermission::ManageMachines,
            CertificationPermission::Manage,
            BillingPermission::Manage,
            DepositPermission::ManageDeposits,
            AccountsPermission::ManagePurchaseOrders,
            InspectionPermission::ManageDamages,
            SalesPermission::Manage,
        ]));
    }

    public function render(): View
    {
        return view('livewire.dashboard.dashboard')->title(__('dashboard.title'));
    }

    private function employee(): User
    {
        /** @var User $employee */
        $employee = Auth::user();

        return $employee;
    }
}
