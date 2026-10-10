@props(['title'])

<div {{ $attributes->class('flex flex-wrap items-center justify-between gap-4') }}>
    <h1 class="text-[2rem] font-medium text-zinc-800 dark:text-white" data-flux-heading>{{ $title }}</h1>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
