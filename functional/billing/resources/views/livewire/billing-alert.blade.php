<div wire:poll.60s>
    @if ($transmissionsCount > 0)
        <flux:callout variant="warning" icon="exclamation-triangle" class="mb-6">
            <flux:callout.heading>
                {{ trans_choice('billing::transmissions.alert.count', $transmissionsCount, ['count' => $transmissionsCount]) }}
            </flux:callout.heading>
            <x-slot name="actions">
                <flux:button size="sm" :href="route('billing.transmissions')" wire:navigate>{{ __('billing::transmissions.alert.open') }}</flux:button>
            </x-slot>
        </flux:callout>
    @endif
</div>
