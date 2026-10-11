<div class="flex flex-col gap-12">
    @canany([
        \Functional\Booking\Access\BookingPermission::ManageReservations->value,
        \Functional\Fleet\Access\FleetPermission::ManageMachines->value,
        \Functional\Certification\Enums\CertificationPermission::Manage->value,
    ])
        @teleport('#top-bar-actions')
            <div class="flex w-full items-center gap-3 sm:w-auto">
                <label for="dashboard-agency" class="eyebrow text-zinc-500 dark:text-white/72">{{ __('dashboard.agency.label') }}</label>
                <div class="relative flex-1 sm:flex-none">
                    <select id="dashboard-agency" wire:model.live="agency" class="h-11 w-full appearance-none truncate rounded-full border border-zinc-950 bg-white ps-4 pe-9 text-sm font-semibold sm:h-9 sm:max-w-none dark:border-white dark:bg-black">
                        <option value="">{{ $this->agencies->firstWhere('id', $this->ownAgencyId)?->name }}</option>
                        @foreach ($this->agencies->where('id', '!=', $this->ownAgencyId) as $agencyOption)
                            <option value="{{ $agencyOption->id }}">{{ $agencyOption->name }}</option>
                        @endforeach
                        <option value="{{ \App\Livewire\Dashboard\Dashboard::ALL_AGENCIES }}">{{ __('dashboard.agency.all') }}</option>
                    </select>
                    <flux:icon.chevron-down variant="micro" class="pointer-events-none absolute end-3 top-1/2 -translate-y-1/2" />
                </div>
            </div>
        @endteleport
    @endcanany

    @can(\Functional\Booking\Access\BookingPermission::ManageReservations->value)
        <h1 class="sr-only">{{ __('dashboard.title') }}</h1>
        <livewire:dashboard.day-operations :agency-id="$this->agencyId" :wire:key="'day-operations-'.($this->agencyId ?? 'all')" />
    @else
        <x-page-heading :title="__('dashboard.title')" />
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

    <div class="grid gap-12 lg:grid-cols-2 lg:gap-10">
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
