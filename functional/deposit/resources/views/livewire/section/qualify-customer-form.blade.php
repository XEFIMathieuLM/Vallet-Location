<div class="space-y-2">
    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    @if ($currentType === null)
        <flux:text>{{ __('deposit::section.qualify_intro') }}</flux:text>
        <div class="flex flex-wrap gap-2">
            @foreach ($customerTypes as $customerType)
                <flux:button size="sm" wire:click="qualify('{{ $customerType->value }}')">{{ $customerType->label() }}</flux:button>
            @endforeach
        </div>
    @else
        <div class="flex flex-wrap items-center gap-2">
            <flux:text>{{ __('deposit::section.customer_type', ['type' => $currentType->label()]) }}</flux:text>
            <flux:modal.trigger name="qualify-customer">
                <flux:button size="xs" variant="ghost">{{ __('deposit::section.change_type') }}</flux:button>
            </flux:modal.trigger>
        </div>

        <flux:modal name="qualify-customer" class="max-w-md">
            <div class="flex flex-col gap-6">
                <x-section-heading :title="__('deposit::section.change_type')" />
                <flux:text>{{ __('deposit::section.change_type_help') }}</flux:text>
                <div class="flex flex-wrap justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('deposit::section.cancel') }}</flux:button>
                    </flux:modal.close>
                    @foreach ($customerTypes as $customerType)
                        @if ($customerType !== $currentType)
                            <flux:button variant="primary" wire:click="qualify('{{ $customerType->value }}')">{{ $customerType->label() }}</flux:button>
                        @endif
                    @endforeach
                </div>
            </div>
        </flux:modal>
    @endif
</div>
