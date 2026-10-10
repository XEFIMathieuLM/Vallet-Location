@props(['title', 'level' => 2])

@php
    $sizeClass = match ((int) $level) {
        3 => 'text-xl',
        4 => 'text-base font-semibold',
        default => 'text-2xl',
    };
@endphp

<h{{ (int) $level }} {{ $attributes->class([$sizeClass, 'font-medium text-zinc-800 dark:text-white']) }} data-flux-heading>{{ $title }}</h{{ (int) $level }}>
