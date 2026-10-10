<div class="space-y-2">
    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <flux:modal.trigger name="collect-deposit">
        <flux:button variant="primary" icon="banknotes">{{ __('deposit::section.collect') }}</flux:button>
    </flux:modal.trigger>

    <flux:modal name="collect-deposit" class="max-w-md">
        <form wire:submit="collect" class="flex flex-col gap-6">
            <x-section-heading :title="__('deposit::section.collect')" />
            <flux:radio.group wire:model="method" :label="__('deposit::section.method')">
                @foreach ($paymentMethods as $paymentMethod)
                    <flux:radio :value="$paymentMethod->value" :label="$paymentMethod->label()" />
                @endforeach
            </flux:radio.group>
            <flux:input wire:model="reference" :label="__('deposit::section.reference')" :description="__('deposit::section.reference_help')" />
            <div class="flex items-center justify-end gap-2">
                <x-loading-hint wire:target="collect" />
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('deposit::section.cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('deposit::section.confirm_collect') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
