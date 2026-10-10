<form wire:submit="save" class="flex flex-wrap items-end gap-2">
    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" class="w-full" />
    @enderror
    <flux:input type="email" wire:model="email" :label="__('certification::certificates.section.customer_email')" class="max-w-sm" />
    <flux:button type="submit" size="sm">{{ __('certification::certificates.section.save_email') }}</flux:button>
    <x-loading-hint wire:target="save" />
</form>
