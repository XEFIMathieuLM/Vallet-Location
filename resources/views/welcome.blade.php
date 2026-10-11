<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => __('welcome.title'), 'hasAppearanceChoice' => false])
    </head>
    <body class="flex min-h-screen flex-col bg-white text-zinc-950 antialiased">
        <header class="border-b border-zinc-100">
            <div class="mx-auto flex h-[60px] max-w-[90rem] items-center justify-between gap-6 px-4 sm:px-10">
                <x-app-logo href="{{ route('home') }}" />
            </div>
        </header>

        <main class="flex flex-1 flex-col bg-black text-white">
            <div class="mx-auto flex w-full max-w-[90rem] flex-1 flex-col justify-between gap-12 px-4 py-12 sm:px-10 sm:py-20">
                <div class="flex max-w-3xl flex-col gap-6">
                    <p class="eyebrow text-white/72">{{ __('welcome.eyebrow') }}</p>
                    <h1 class="display-xl" data-flux-heading>{{ __('welcome.title') }}</h1>
                    <p class="max-w-xl text-lg text-white/80">{{ __('welcome.lead') }}</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <x-tile variant="light" :href="auth()->check() ? route('dashboard') : route('login')" class="min-h-52 justify-between gap-8 border-0 hover:bg-zinc-50" data-welcome-staff>
                        <span class="flex flex-col gap-3">
                            <span class="eyebrow text-zinc-500">{{ __('welcome.staff.eyebrow') }}</span>
                            <span class="heading-lg">{{ __('welcome.staff.title') }}</span>
                            <span class="text-zinc-700">{{ __('welcome.staff.description') }}</span>
                        </span>
                        <span class="inline-flex items-center gap-2 font-bold underline underline-offset-4">{{ __('welcome.staff.action') }} <flux:icon.arrow-right variant="micro" /></span>
                    </x-tile>

                    <x-tile variant="light" :href="route('portal.login')" class="min-h-52 justify-between gap-8 border-0 hover:bg-zinc-50" data-welcome-customer>
                        <span class="flex flex-col gap-3">
                            <span class="eyebrow text-zinc-500">{{ __('welcome.customer.eyebrow') }}</span>
                            <span class="heading-lg">{{ __('welcome.customer.title') }}</span>
                            <span class="text-zinc-700">{{ __('welcome.customer.description') }}</span>
                        </span>
                        <span class="inline-flex items-center gap-2 font-bold underline underline-offset-4">{{ __('welcome.customer.action') }} <flux:icon.arrow-right variant="micro" /></span>
                    </x-tile>
                </div>

                <div aria-hidden="true" class="max-md:hidden">
                    <div class="flex items-end justify-end gap-10">
                        <x-fleet::pictogram category="mini-pelle" class="w-48" />
                        <x-fleet::pictogram category="nacelle" class="w-80" />
                        <x-fleet::pictogram category="compacteur" class="w-40" />
                    </div>
                    <div class="h-[3px] bg-white"></div>
                </div>
            </div>
        </main>

        <footer class="border-t border-white/16 bg-black text-white">
            <div class="mx-auto flex max-w-[90rem] flex-wrap items-center justify-between gap-4 px-4 py-6 text-xs text-white/72 sm:px-10">
                <x-app-logo :inverse="true" />
                <span>{{ __('welcome.copyright', ['year' => now('Europe/Paris')->year]) }}</span>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>
