<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white text-zinc-950 antialiased dark:bg-black dark:text-white">
        <flux:sidebar sticky collapsible="mobile" class="dark border-e border-white/16 bg-black text-white">
            <flux:sidebar.header class="min-h-11">
                <x-app-logo :sidebar="true" :inverse="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    @can(\Functional\Inspection\Access\InspectionPermission::ManageDamages->value)
                        <flux:sidebar.item icon="exclamation-triangle" :href="route('inspection.damages')" :current="request()->routeIs('inspection.damages')" wire:navigate>
                            {{ __('inspection::damages.navigation') }}
                        </flux:sidebar.item>
                    @endcan
                    @can(\Functional\Inspection\Access\InspectionPermission::ManageInspectionViews->value)
                        <flux:sidebar.item icon="camera" :href="route('inspection.category-views.index')" :current="request()->routeIs('inspection.category-views.*')" wire:navigate>
                            {{ __('inspection::views.navigation') }}
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>

                @can(\Functional\Booking\Access\BookingPermission::ManageReservations->value)
                    <flux:sidebar.group :heading="__('booking::reservations.navigation.heading')" class="grid">
                        <flux:sidebar.item icon="magnifying-glass" :href="route('availability.index')" :current="request()->routeIs('availability.*')" wire:navigate>
                            {{ __('booking::reservations.navigation.availability') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="calendar-days" :href="route('reservations.index')" :current="request()->routeIs('reservations.*')" wire:navigate>
                            {{ __('booking::reservations.navigation.reservations') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="table-cells" :href="route('planning.index')" :current="request()->routeIs('planning.*')" wire:navigate>
                            {{ __('booking::reservations.navigation.planning') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan

                @can(\Functional\Billing\Enums\BillingPermission::Manage->value)
                    <flux:sidebar.group :heading="__('billing::navigation.heading')" class="grid">
                        <flux:sidebar.item icon="paper-airplane" :href="route('billing.transmissions')" :current="request()->routeIs('billing.transmissions')" wire:navigate>
                            {{ __('billing::navigation.transmissions') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="arrow-down-tray" :href="route('billing.exports')" :current="request()->routeIs('billing.exports*')" wire:navigate>
                            {{ __('billing::navigation.exports') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="chart-bar" :href="route('billing.statement')" :current="request()->routeIs('billing.statement')" wire:navigate>
                            {{ __('billing::navigation.statement') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan

                @canany([\Functional\Deposit\Access\DepositPermission::ManageDeposits->value, \Functional\Deposit\Access\DepositPermission::ManageDepositRates->value])
                    <flux:sidebar.group :heading="__('deposit::navigation.heading')" class="grid">
                        @can(\Functional\Deposit\Access\DepositPermission::ManageDeposits->value)
                            <flux:sidebar.item icon="banknotes" :href="route('deposit.pending.index')" :current="request()->routeIs('deposit.pending.*')" wire:navigate>
                                {{ __('deposit::navigation.pending') }}
                            </flux:sidebar.item>
                        @endcan
                        @can(\Functional\Deposit\Access\DepositPermission::ManageDepositRates->value)
                            <flux:sidebar.item icon="adjustments-horizontal" :href="route('deposit.rates.index')" :current="request()->routeIs('deposit.rates.*')" wire:navigate>
                                {{ __('deposit::navigation.rates') }}
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endcanany
                @can(\Functional\Sales\Access\SalesPermission::Manage->value)
                    <flux:sidebar.group :heading="__('sales::sales.navigation.heading')" class="grid">
                        <flux:sidebar.item icon="banknotes" :href="route('sales.index')" :current="request()->routeIs('sales.*')" wire:navigate>
                            {{ __('sales::sales.navigation.sales') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan

                @can(\Functional\Certification\Enums\CertificationPermission::Manage->value)
                    <flux:sidebar.group :heading="__('certification::navigation.heading')" class="grid">
                        <flux:sidebar.item icon="shield-check" :href="route('certification.machines')" :current="request()->routeIs('certification.machines*')" wire:navigate>
                            {{ __('certification::navigation.reports') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="envelope" :href="route('certification.certificates')" :current="request()->routeIs('certification.certificates')" wire:navigate>
                            {{ __('certification::navigation.certificates') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan

                @canany([\Functional\Accounts\Access\AccountsPermission::ManageKeyAccounts->value, \Functional\Accounts\Access\AccountsPermission::ManagePurchaseOrders->value])
                    <flux:sidebar.group :heading="__('accounts::key_accounts.navigation.heading')" class="grid">
                        @can(\Functional\Accounts\Access\AccountsPermission::ManageKeyAccounts->value)
                            <flux:sidebar.item icon="building-office-2" :href="route('accounts.key-accounts')" :current="request()->routeIs('accounts.key-accounts')" wire:navigate>
                                {{ __('accounts::key_accounts.navigation.key_accounts') }}
                            </flux:sidebar.item>
                        @endcan
                        @can(\Functional\Accounts\Access\AccountsPermission::ManagePurchaseOrders->value)
                            <flux:sidebar.item icon="document-text" :href="route('accounts.missing-purchase-orders')" :current="request()->routeIs('accounts.missing-purchase-orders')" wire:navigate>
                                {{ __('accounts::purchase_orders.missing.navigation') }}
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endcanany

                @canany([\Functional\Portal\Access\PortalPermission::HandleRequests->value, \Functional\Portal\Access\PortalPermission::ManagePrices->value])
                    <flux:sidebar.group :heading="__('portal::navigation.staff.heading')" class="grid">
                        @can(\Functional\Portal\Access\PortalPermission::HandleRequests->value)
                            <flux:sidebar.item icon="inbox-arrow-down" :href="route('portal.staff.requests')" :current="request()->routeIs('portal.staff.requests')" wire:navigate>
                                <div class="flex items-center justify-between gap-2">
                                    {{ __('portal::navigation.staff.requests') }}
                                    <livewire:portal.pending-requests-badge />
                                </div>
                            </flux:sidebar.item>
                        @endcan
                        @can(\Functional\Portal\Access\PortalPermission::ManagePrices->value)
                            <flux:sidebar.item icon="currency-euro" :href="route('portal.staff.prices')" :current="request()->routeIs('portal.staff.prices')" wire:navigate>
                                {{ __('portal::navigation.staff.prices') }}
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endcanany

                @can(\Functional\Fleet\Access\FleetPermission::ManageMachines->value)
                    <flux:sidebar.group :heading="__('fleet::machines.navigation.fleet')" class="grid">
                        <flux:sidebar.item icon="truck" :href="route('machines.index')" :current="request()->routeIs('machines.*')" wire:navigate>
                            {{ __('fleet::machines.index.title') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan

                @can(\App\Access\AppPermission::ManageUsers->value)
                    <flux:sidebar.group :heading="__('users.title')" class="grid">
                        <flux:sidebar.item icon="users" :href="route('users.index')" :current="request()->routeIs('users.*')" wire:navigate>
                            {{ __('users.title') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <x-top-bar :title="$title ?? null">
            <x-slot:start>
                <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
                <x-app-logo href="{{ route('dashboard') }}" class="lg:hidden" wire:navigate />
            </x-slot:start>

            <x-slot:end>
                <x-mobile-user-menu class="ms-auto lg:hidden" />
            </x-slot:end>
        </x-top-bar>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
