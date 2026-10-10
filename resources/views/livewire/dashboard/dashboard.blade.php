<div class="flex flex-col gap-8">
    <x-page-heading :title="__('dashboard.title')">
        @canany([
            \Functional\Booking\Access\BookingPermission::ManageReservations->value,
            \Functional\Fleet\Access\FleetPermission::ManageMachines->value,
            \Functional\Certification\Enums\CertificationPermission::Manage->value,
        ])
            <x-slot:actions>
                <flux:select wire:model.live="agency" :label="__('dashboard.agency.label')" class="min-w-56">
                    <flux:select.option value="">{{ $this->agencies->firstWhere('id', $this->ownAgencyId)?->name }}</flux:select.option>
                    @foreach ($this->agencies->where('id', '!=', $this->ownAgencyId) as $agencyOption)
                        <flux:select.option :value="$agencyOption->id">{{ $agencyOption->name }}</flux:select.option>
                    @endforeach
                    <flux:select.option :value="\App\Livewire\Dashboard\Dashboard::ALL_AGENCIES">{{ __('dashboard.agency.all') }}</flux:select.option>
                </flux:select>
            </x-slot:actions>
        @endcanany
    </x-page-heading>

    @can(\Functional\Booking\Access\BookingPermission::ManageReservations->value)
        <livewire:dashboard.day-operations :agency-id="$this->agencyId" :wire:key="'day-operations-'.($this->agencyId ?? 'all')" />
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

    <div class="grid gap-8 lg:grid-cols-2">
        @can(\Functional\Fleet\Access\FleetPermission::ManageMachines->value)
            <livewire:dashboard.fleet-status :agency-id="$this->agencyId" :wire:key="'fleet-status-'.($this->agencyId ?? 'all')" />
        @endcan
        @can(\Functional\Certification\Enums\CertificationPermission::Manage->value)
            <livewire:dashboard.vgp-watch :agency-id="$this->agencyId" :wire:key="'vgp-watch-'.($this->agencyId ?? 'all')" />
        @endcan
    </div>

    @unless ($this->hasAnySection)
        <x-empty-state :heading="__('dashboard.empty.heading')" :description="__('dashboard.empty.description')" />
    @endunless
</div>
