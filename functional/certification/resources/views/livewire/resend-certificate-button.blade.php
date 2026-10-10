<div class="space-y-2">
    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror
    <div class="flex items-center gap-2">
        <flux:button size="sm" icon="paper-airplane" wire:click="resend">{{ __('certification::certificates.section.resend') }}</flux:button>
        <x-loading-hint wire:target="resend" />
    </div>
</div>
