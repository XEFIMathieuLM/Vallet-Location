<div wire:poll.60s>
    @if ($certificatesCount > 0)
        <flux:callout variant="warning" icon="exclamation-triangle" class="mb-6">
            <flux:callout.heading>
                {{ trans_choice('certification::certificates.alert.count', $certificatesCount, ['count' => $certificatesCount]) }}
            </flux:callout.heading>
            <x-slot name="actions">
                <flux:button size="sm" :href="route('certification.certificates')" wire:navigate>{{ __('certification::certificates.alert.open') }}</flux:button>
            </x-slot>
        </flux:callout>
    @endif
</div>
