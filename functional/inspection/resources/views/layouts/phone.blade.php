<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => __('inspection::phone.title')])
        <meta name="robots" content="noindex, nofollow" />
        <meta name="referrer" content="no-referrer" />
    </head>
    <body class="min-h-screen bg-white text-zinc-950 antialiased dark:bg-black dark:text-white">
        <header class="bg-black text-white">
            <div class="mx-auto flex h-[60px] w-full max-w-md items-center justify-between gap-4 px-4">
                <x-app-logo :inverse="true" />
                <span class="eyebrow text-white/72">{{ __('inspection::phone.title') }}</span>
            </div>
        </header>

        <main class="mx-auto flex w-full max-w-md flex-col gap-4 p-4 [&_[data-flux-button]]:min-h-12">
            {{ $slot }}
        </main>

        @fluxScripts
    </body>
</html>
