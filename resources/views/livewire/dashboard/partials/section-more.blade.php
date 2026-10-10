@if ($section->hasMore())
    <div class="mt-auto flex flex-wrap items-center justify-between gap-2 border-t border-black/10 pt-3 text-sm dark:border-white/16">
        <span class="opacity-75">{{ __('dashboard.section.more', ['shown' => $section->items->count(), 'total' => $section->total]) }}</span>
        <a href="{{ $url }}" wire:navigate class="font-bold underline underline-offset-4">{{ $label }}</a>
    </div>
@endif
