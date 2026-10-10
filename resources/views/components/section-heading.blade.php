@props(['title', 'level' => 2])

@php
    $sizeClass = match ((int) $level) {
        3 => 'heading-md',
        4 => 'heading-sm',
        default => 'heading-lg',
    };
@endphp

<h{{ (int) $level }} {{ $attributes->class([$sizeClass, 'text-zinc-950 dark:text-white']) }} data-flux-heading>{{ $title }}</h{{ (int) $level }}>
