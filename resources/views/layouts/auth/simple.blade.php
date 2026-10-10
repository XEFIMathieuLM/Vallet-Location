<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white text-zinc-950 antialiased dark:bg-black dark:text-white">
        <x-auth-split :eyebrow="__('auth.staff_space')" :home-url="route('home')">
            {{ $slot }}
        </x-auth-split>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
