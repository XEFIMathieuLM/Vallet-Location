<div>
    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" class="mb-2" />
    @enderror
    <flux:modal.trigger :name="'hand-delivery-'.$reservation->id">
        <flux:button size="sm" icon="hand-raised">{{ __('certification::certificates.section.hand_delivery') }}</flux:button>
    </flux:modal.trigger>
    <flux:modal :name="'hand-delivery-'.$reservation->id" class="max-w-md">
        <div class="space-y-4">
            <x-section-heading :title="__('certification::certificates.section.hand_delivery')" level="3" />
            <flux:text>{{ __('certification::certificates.section.hand_delivery_confirm') }}</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('certification::certificates.section.cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="record">{{ __('certification::certificates.section.confirm_hand_delivery') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
