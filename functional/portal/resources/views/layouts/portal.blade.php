<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <x-app-logo href="{{ route('portal.search') }}" wire:navigate />

            <flux:navbar class="-mb-px ms-4 max-lg:hidden">
                <flux:navbar.item icon="magnifying-glass" :href="route('portal.search')" :current="request()->routeIs('portal.search')" wire:navigate>
                    {{ __('portal::navigation.search') }}
                </flux:navbar.item>
                <flux:navbar.item icon="inbox" :href="route('portal.requests')" :current="request()->routeIs('portal.requests')" wire:navigate>
                    {{ __('portal::navigation.requests') }}
                </flux:navbar.item>
                <flux:navbar.item icon="calendar-days" :href="route('portal.reservations')" :current="request()->routeIs('portal.reservations')" wire:navigate>
                    {{ __('portal::navigation.reservations') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <flux:dropdown position="bottom" align="end">
                <flux:profile :name="auth('customer')->user()?->name" icon:trailing="chevron-down" />
                <flux:menu>
                    <flux:menu.item icon="magnifying-glass" :href="route('portal.search')" class="lg:hidden" wire:navigate>{{ __('portal::navigation.search') }}</flux:menu.item>
                    <flux:menu.item icon="inbox" :href="route('portal.requests')" class="lg:hidden" wire:navigate>{{ __('portal::navigation.requests') }}</flux:menu.item>
                    <flux:menu.item icon="calendar-days" :href="route('portal.reservations')" class="lg:hidden" wire:navigate>{{ __('portal::navigation.reservations') }}</flux:menu.item>
                    <flux:menu.item icon="user" :href="route('portal.account')" wire:navigate>{{ __('portal::navigation.account') }}</flux:menu.item>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('portal.logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="portal-logout-button">
                            {{ __('portal::navigation.logout') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <flux:main container>
            {{ $slot }}
        </flux:main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
