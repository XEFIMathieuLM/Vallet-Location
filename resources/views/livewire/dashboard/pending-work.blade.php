<section wire:poll.60s class="flex flex-col gap-6 bg-black pt-6 text-white sm:pt-8 dark:border dark:border-white/16">
    <h2 class="heading-lg px-6 sm:px-8" data-flux-heading>{{ __('dashboard.pending.heading') }}</h2>

    <div class="grid gap-px border-t border-white/16 bg-white/16 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($counters as $counter)
            <a
                href="{{ $counter->url }}"
                wire:navigate
                wire:key="counter-{{ $counter->key }}"
                data-counter="{{ $counter->key }}" data-highlighted="{{ $counter->isHighlighted() ? 'true' : 'false' }}"
                @class([
                    'flex min-h-20 items-center justify-between gap-4 bg-black px-6 py-4 hover:bg-zinc-900 sm:px-8',
                    'font-semibold text-white' => $counter->isHighlighted(),
                    'text-white/72' => ! $counter->isHighlighted(),
                ])
            >
                <span>{{ __('dashboard.pending.'.$counter->key) }}</span>
                @if ($counter->isHighlighted())
                    <span class="flex min-w-12 items-center justify-center bg-accent px-3 py-1 text-2xl font-bold text-black">{{ $counter->count }}</span>
                @else
                    <span class="text-2xl font-bold">{{ $counter->count }}</span>
                @endif
            </a>
        @endforeach
    </div>
</section>
