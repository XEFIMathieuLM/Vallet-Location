<section wire:poll.60s class="flex flex-col gap-4">
    <x-section-heading :title="__('dashboard.pending.heading')" />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($counters as $counter)
            <a
                href="{{ $counter->url }}"
                wire:navigate
                wire:key="counter-{{ $counter->key }}"
                data-counter="{{ $counter->key }}" data-highlighted="{{ $counter->isHighlighted() ? 'true' : 'false' }}"
                class="flex items-center justify-between gap-4 rounded-xl border border-zinc-200 p-4 hover:bg-zinc-50 focus-visible:outline-2 dark:border-zinc-700 dark:hover:bg-zinc-700/50"
            >
                <flux:text variant="strong">{{ __('dashboard.pending.'.$counter->key) }}</flux:text>
                @if ($counter->isHighlighted())
                    <flux:badge color="amber" size="lg">{{ $counter->count }}</flux:badge>
                @else
                    <flux:text class="text-lg">{{ $counter->count }}</flux:text>
                @endif
            </a>
        @endforeach
    </div>
</section>
