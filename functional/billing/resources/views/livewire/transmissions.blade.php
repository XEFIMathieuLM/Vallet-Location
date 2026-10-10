<div class="space-y-6">
    <x-page-heading :title="__('billing::transmissions.screen.title')" />
    <flux:text>{{ __('billing::transmissions.screen.intro', ['hours' => config('billing.alert_after_hours')]) }}</flux:text>

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    @if ($transmissions->isEmpty())
        <div class="space-y-2">
            <flux:text>{{ __('billing::transmissions.screen.empty') }}</flux:text>
            <flux:link :href="route('billing.statement')" wire:navigate>{{ __('billing::transmissions.screen.open_statement') }}</flux:link>
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('billing::transmissions.screen.reservation') }}</flux:table.column>
                <flux:table.column>{{ __('billing::transmissions.screen.customer') }}</flux:table.column>
                <flux:table.column>{{ __('billing::transmissions.screen.type') }}</flux:table.column>
                <flux:table.column>{{ __('billing::transmissions.screen.date') }}</flux:table.column>
                <flux:table.column>{{ __('billing::transmissions.screen.reason') }}</flux:table.column>
                <flux:table.column />
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($transmissions as $transmission)
                    <flux:table.row :key="$transmission->id">
                        <flux:table.cell>
                            <flux:link :href="route('reservations.show', $transmission->reservation)" wire:navigate>{{ $transmission->reservation->machine->reference }}</flux:link>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="space-y-1">
                                <div>{{ $transmission->reservation->customer->name }}</div>
                                @if ($transmission->failure_reason === \Functional\Billing\Enums\TransmissionFailureReason::CustomerUnknown)
                                    <div class="flex items-end gap-2">
                                        <flux:input size="sm" wire:model="customerRefs.{{ $transmission->reservation->customer_id }}" :label="__('billing::transmissions.screen.customer_ref')" />
                                        <flux:button size="xs" wire:click="saveCustomerRef({{ $transmission->reservation->customer_id }})">{{ __('billing::transmissions.screen.save_customer_ref') }}</flux:button>
                                    </div>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $transmission->billablePeriod?->kind->label() ?? __('billing::transmissions.screen.damage') }}
                        </flux:table.cell>
                        <flux:table.cell>{{ ($transmission->last_attempt_at ?? $transmission->created_at)->format('d/m/Y H:i') }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="space-y-2">
                                <flux:badge size="sm" :color="$transmission->status->color()">{{ $transmission->status->label() }}</flux:badge>
                                @include('billing::partials.transmission-reason', ['transmission' => $transmission])
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($transmission->state()->canBeRetried())
                                <flux:button size="xs" icon="arrow-path" wire:click="retry({{ $transmission->id }})">{{ __('billing::transmissions.screen.retry') }}</flux:button>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
