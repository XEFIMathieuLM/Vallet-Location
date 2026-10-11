@props(['eyebrow' => null, 'homeUrl'])

<div class="grid min-h-svh lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
    <div class="relative flex flex-col justify-between gap-10 overflow-hidden bg-black px-6 py-6 text-white sm:px-10 lg:py-10">
        <x-app-logo :inverse="true" :href="$homeUrl" wire:navigate />

        <div class="flex flex-col gap-4 max-lg:hidden">
            @if ($eyebrow !== null)
                <p class="eyebrow text-white/72">{{ $eyebrow }}</p>
            @endif
            <p class="display-xl max-w-lg">{{ config('app.name') }}</p>
        </div>

        <div aria-hidden="true" class="relative max-lg:hidden">
            <div class="flex items-end gap-8">
                <x-fleet::pictogram category="nacelle" class="w-2/3 max-w-96" />
                <x-fleet::pictogram category="compacteur" class="w-1/4 max-w-40" />
            </div>
            <div class="h-[3px] bg-white"></div>
        </div>
    </div>

    <div class="flex flex-col justify-center px-6 py-10 sm:px-10">
        <div class="mx-auto flex w-full max-w-md flex-col gap-6">
            @if ($eyebrow !== null)
                <p class="eyebrow text-zinc-500 lg:hidden dark:text-white/72">{{ $eyebrow }}</p>
            @endif
            {{ $slot }}
        </div>
    </div>
</div>
