@if ($section->hasMore())
    <div class="flex flex-wrap items-center justify-between gap-2 px-3">
        <flux:text size="sm">{{ __('dashboard.section.more', ['shown' => $section->items->count(), 'total' => $section->total]) }}</flux:text>
        <flux:link :href="$url" wire:navigate class="text-sm">{{ $label }}</flux:link>
    </div>
@endif
