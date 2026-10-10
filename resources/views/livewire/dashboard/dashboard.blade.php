<div class="flex flex-col gap-8">
    <x-page-heading :title="__('dashboard.title')" />

    @can(\Functional\Booking\Access\BookingPermission::ManageReservations->value)
        <livewire:dashboard.day-operations :agency-id="$this->agencyId" />
    @endcan

    @canany([
        \Functional\Billing\Enums\BillingPermission::Manage->value,
        \Functional\Certification\Enums\CertificationPermission::Manage->value,
        \Functional\Deposit\Access\DepositPermission::ManageDeposits->value,
        \Functional\Accounts\Access\AccountsPermission::ManagePurchaseOrders->value,
        \Functional\Inspection\Access\InspectionPermission::ManageDamages->value,
        \Functional\Sales\Access\SalesPermission::Manage->value,
    ])
        <livewire:dashboard.pending-work />
    @endcanany

    @unless ($this->hasAnySection)
        <x-empty-state :heading="__('dashboard.empty.heading')" :description="__('dashboard.empty.description')" />
    @endunless
</div>
