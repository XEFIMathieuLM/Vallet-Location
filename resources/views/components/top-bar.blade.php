@props(['title' => null])

<flux:header {{ $attributes->class('min-h-[60px] flex-wrap gap-x-3 border-b border-zinc-100 bg-white dark:border-white/16 dark:bg-black') }} data-top-bar>
    {{ $start ?? '' }}

    <p class="flex min-w-0 items-baseline gap-2 text-sm max-lg:hidden">
        @if (filled($title))
            <span class="truncate font-semibold text-zinc-950 dark:text-white">{{ $title }}</span>
            <span aria-hidden="true" class="text-zinc-300 dark:text-white/40">·</span>
        @endif
        <span class="whitespace-nowrap text-zinc-500 dark:text-white/72">{{ now('Europe/Paris')->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</span>
    </p>

    <div id="top-bar-actions" class="flex items-center gap-3 empty:hidden max-sm:order-last max-sm:w-full max-sm:pb-3 sm:ms-auto"></div>

    {{ $end ?? '' }}
</flux:header>
