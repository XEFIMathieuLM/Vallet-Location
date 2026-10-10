<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    @can('damages.manage')
                        <flux:sidebar.item icon="exclamation-triangle" :href="route('inspection.damages')" :current="request()->routeIs('inspection.damages')" wire:navigate>
                            {{ __('inspection::damages.navigation') }}
                        </flux:sidebar.item>
                    @endcan
                    @can('inspection_views.manage')
                        <flux:sidebar.item icon="camera" :href="route('inspection.category-views.index')" :current="request()->routeIs('inspection.category-views.*')" wire:navigate>
                            {{ __('inspection::views.navigation') }}
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>

                @can('reservations.manage')
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

                @can('billing.manage')
                    <flux:sidebar.group :heading="__('billing::navigation.heading')" class="grid">
                        <flux:sidebar.item icon="paper-airplane" :href="route('billing.transmissions')" :current="request()->routeIs('billing.transmissions')" wire:navigate>
                            {{ __('billing::navigation.transmissions') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="arrow-down-tray" :href="route('billing.exports')" :current="request()->routeIs('billing.exports*')" wire:navigate>
                            {{ __('billing::navigation.exports') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan

                @can('machines.manage')
                    <flux:sidebar.group :heading="__('fleet::machines.navigation.fleet')" class="grid">
                        <flux:sidebar.item icon="truck" :href="route('machines.index')" :current="request()->routeIs('machines.*')" wire:navigate>
                            {{ __('fleet::machines.index.title') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan

                @can('users.manage')
                    <flux:sidebar.group :heading="__('users.title')" class="grid">
                        <flux:sidebar.item icon="users" :href="route('users.index')" :current="request()->routeIs('users.*')" wire:navigate>
                            {{ __('users.title') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
