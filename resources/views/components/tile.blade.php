@props(['variant' => 'soft', 'href' => null])

@php
    $tag = $href === null ? 'div' : 'a';
    $variantClasses = match ($variant) {
        'light' => 'bg-white text-zinc-950 border border-zinc-200 dark:bg-black dark:text-white dark:border-white/16',
        'dark' => 'bg-black text-white dark:bg-zinc-900',
        'accent' => 'bg-accent text-black',
        default => 'bg-zinc-50 text-zinc-950 dark:bg-zinc-900 dark:text-white',
    };
@endphp

<{{ $tag }} @if ($href !== null) href="{{ $href }}" @endif data-tile="{{ $variant }}" {{ $attributes->class(['flex flex-col p-6 lg:p-8', $variantClasses]) }}>
    {{ $slot }}
</{{ $tag }}>
