<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['hasAppearanceChoice' => false])
    </head>
    <body class="min-h-screen bg-white text-zinc-950 antialiased">
        <x-auth-split :eyebrow="__('portal::navigation.hero_eyebrow')" :home-url="route('portal.login')">
            {{ $slot }}
        </x-auth-split>

        @fluxScripts
    </body>
</html>
