@props(['title', 'eyebrow' => null])

<div {{ $attributes->class('flex flex-wrap items-end justify-between gap-x-6 gap-y-4') }}>
    <div class="flex min-w-0 flex-col gap-2">
        @if ($eyebrow !== null)
            <p class="eyebrow text-zinc-500 dark:text-white/72">{{ $eyebrow }}</p>
        @endif
        <h1 class="display-md text-zinc-950 lg:display-lg dark:text-white" data-flux-heading>{{ $title }}</h1>
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
