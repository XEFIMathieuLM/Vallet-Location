<div wire:loading.delay {{ $attributes->class('text-sm text-zinc-600 dark:text-zinc-300') }} role="status">
    <span class="flex items-center gap-2">
        <flux:icon.loading variant="micro" />
        {{ __('screens.loading') }}
    </span>
</div>
