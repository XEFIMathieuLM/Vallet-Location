<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => __('inspection::phone.title')])
        <meta name="robots" content="noindex, nofollow" />
        <meta name="referrer" content="no-referrer" />
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-zinc-900">
        <main class="mx-auto flex w-full max-w-md flex-col gap-4 p-4">
            {{ $slot }}
        </main>

        @fluxScripts
    </body>
</html>
