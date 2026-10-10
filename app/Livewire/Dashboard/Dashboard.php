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
use Functional\Inspection\Access\InspectionPermission;
use Functional\Sales\Access\SalesPermission;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    #[Computed]
    public function agencyId(): ?int
    {
        return $this->employee()->agencyId();
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
