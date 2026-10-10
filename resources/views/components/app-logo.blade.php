@props([
    'sidebar' => false,
    'inverse' => false,
])

@php
    $wordmarkClasses = $inverse ? 'text-white' : 'text-zinc-950 dark:text-white';
    $blockClasses = $inverse ? 'bg-white text-black' : 'bg-zinc-950 text-white dark:bg-white dark:text-black';
@endphp

<a {{ $attributes->class('group/logo inline-flex h-10 shrink-0 items-center gap-1.5') }} aria-label="{{ config('app.name') }}" data-app-logo>
    <span aria-hidden="true" class="text-[1.375rem] leading-none font-extrabold tracking-tight {{ $wordmarkClasses }}">VALLET</span>
    <span aria-hidden="true" class="px-1.5 py-1 text-[0.5625rem] leading-none font-bold tracking-[0.2em] {{ $blockClasses }}">LOCATION</span>
</a>
