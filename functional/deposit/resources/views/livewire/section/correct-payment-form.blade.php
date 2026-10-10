<div class="space-y-2">
    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <flux:modal.trigger name="correct-deposit">
        <flux:button size="xs" variant="ghost" icon="pencil-square">{{ __('deposit::section.correct') }}</flux:button>
    </flux:modal.trigger>

    <flux:modal name="correct-deposit" class="max-w-md">
        <form wire:submit="correct" class="flex flex-col gap-6">
            <x-section-heading :title="__('deposit::section.correct')" />
            <flux:radio.group wire:model="method" :label="__('deposit::section.method')">
                @foreach ($paymentMethods as $paymentMethod)
                    <flux:radio :value="$paymentMethod->value" :label="$paymentMethod->label()" />
                @endforeach
            </flux:radio.group>
            <flux:input wire:model="reference" :label="__('deposit::section.reference')" />
            <flux:textarea wire:model="reason" :label="__('deposit::section.reason')" rows="2" />
            <div class="flex items-center justify-end gap-2">
                <x-loading-hint wire:target="correct" />
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('deposit::section.cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('deposit::section.confirm_correct') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
