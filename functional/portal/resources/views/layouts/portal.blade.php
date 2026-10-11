<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['hasAppearanceChoice' => false])
    </head>
    <body class="flex min-h-screen flex-col bg-white text-zinc-950 antialiased">
        <header class="border-b border-zinc-100 bg-white">
            <div class="mx-auto flex h-[60px] max-w-[90rem] items-center gap-6 px-4 sm:px-10">
                <x-app-logo href="{{ route('portal.search') }}" wire:navigate />

                <flux:navbar class="h-full max-lg:hidden">
                    <flux:navbar.item :href="route('portal.search')" :current="request()->routeIs('portal.search')" wire:navigate>
                        {{ __('portal::navigation.search') }}
                    </flux:navbar.item>
                    <flux:navbar.item :href="route('portal.requests')" :current="request()->routeIs('portal.requests')" wire:navigate>
                        {{ __('portal::navigation.requests') }}
                    </flux:navbar.item>
                    <flux:navbar.item :href="route('portal.reservations')" :current="request()->routeIs('portal.reservations')" wire:navigate>
                        {{ __('portal::navigation.reservations') }}
                    </flux:navbar.item>
                </flux:navbar>

                <flux:spacer />

                <flux:dropdown position="bottom" align="end">
                    <flux:profile :name="auth('customer')->user()?->name" icon:trailing="chevron-down" class="max-w-56 max-sm:[&>span]:hidden" />
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
            </div>
        </header>

        <main class="flex flex-1 flex-col">
            {{ $slot }}
        </main>

        <footer class="bg-black text-white" data-portal-footer>
            <div class="mx-auto grid max-w-[90rem] gap-10 px-4 py-14 sm:grid-cols-3 sm:px-10">
                <div class="flex flex-col gap-4">
                    <x-app-logo :inverse="true" href="{{ route('portal.search') }}" wire:navigate />
                    <p class="max-w-xs text-sm text-white/72">{{ __('portal::navigation.footer.tagline') }}</p>
                </div>
                <nav class="flex flex-col gap-3 text-sm" aria-label="{{ __('portal::navigation.footer.heading') }}">
                    <p class="eyebrow text-white/72">{{ __('portal::navigation.footer.heading') }}</p>
                    <a href="{{ route('portal.search') }}" wire:navigate class="hover:underline">{{ __('portal::navigation.search') }}</a>
                    <a href="{{ route('portal.requests') }}" wire:navigate class="hover:underline">{{ __('portal::navigation.requests') }}</a>
                    <a href="{{ route('portal.reservations') }}" wire:navigate class="hover:underline">{{ __('portal::navigation.reservations') }}</a>
                    <a href="{{ route('portal.account') }}" wire:navigate class="hover:underline">{{ __('portal::navigation.account') }}</a>
                </nav>
            </div>
            <div class="mx-auto max-w-[90rem] border-t border-white/16 px-4 py-6 text-xs text-white/72 sm:px-10">
                {{ __('portal::navigation.footer.copyright', ['year' => now('Europe/Paris')->year]) }}
            </div>
        </footer>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
