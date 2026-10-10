<div class="space-y-6">
    <x-page-heading :title="__('accounts::purchase_orders.missing.title')" />
    <flux:text>{{ __('accounts::purchase_orders.missing.intro', ['days' => config('accounts.highlight_days_before_departure')]) }}</flux:text>

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <div class="max-w-xs">
        <flux:select wire:model.live="agencyId" :label="__('accounts::purchase_orders.missing.agency')">
            <flux:select.option value="">{{ __('accounts::purchase_orders.missing.all_agencies') }}</flux:select.option>
            @foreach ($agencies as $agency)
                <flux:select.option :value="$agency->id">{{ $agency->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @if ($reservations->isEmpty())
        <x-empty-state :heading="__('accounts::purchase_orders.missing.empty_heading')" :description="__('accounts::purchase_orders.missing.empty')" />
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('accounts::purchase_orders.missing.departure') }}</flux:table.column>
                <flux:table.column>{{ __('accounts::purchase_orders.missing.customer') }}</flux:table.column>
                <flux:table.column>{{ __('accounts::purchase_orders.missing.machine') }}</flux:table.column>
                <flux:table.column>{{ __('accounts::purchase_orders.missing.agency') }}</flux:table.column>
                <flux:table.column>{{ __('accounts::purchase_orders.section.number') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($reservations as $reservation)
                    <flux:table.row :key="$reservation->id">
                        <flux:table.cell>
                            @if (in_array($reservation->id, $highlightedIds, true))
                                <flux:badge size="sm" color="amber" icon="exclamation-triangle">{{ $reservation->start_date->format('d/m/Y') }}</flux:badge>
                            @else
                                {{ $reservation->start_date->format('d/m/Y') }}
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $reservation->customer->name }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:link :href="route('reservations.show', $reservation)" wire:navigate>{{ $reservation->machine->reference }}</flux:link>
                        </flux:table.cell>
                        <flux:table.cell>{{ $reservation->machine->agency->name }}</flux:table.cell>
                        <flux:table.cell>
                            <form wire:submit="save({{ $reservation->id }})" class="flex items-end gap-2">
                                <flux:input size="sm" wire:model="numbers.{{ $reservation->id }}" :aria-label="__('accounts::purchase_orders.section.number')" maxlength="{{ config('accounts.purchase_order_max_length') }}" />
                                <flux:button type="submit" size="sm">{{ __('accounts::purchase_orders.section.enter') }}</flux:button>
                            </form>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
