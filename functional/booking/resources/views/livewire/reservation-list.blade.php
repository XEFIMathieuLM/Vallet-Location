<div class="flex flex-col gap-6">
    <x-page-heading :title="__('booking::reservations.list.title')" />

    <div class="grid items-end gap-4 md:grid-cols-5">
        <flux:select wire:model.live="status" :label="__('booking::reservations.fields.status')">
            <flux:select.option value="">{{ __('booking::reservations.list.all_statuses') }}</flux:select.option>
            @foreach ($statuses as $statusOption)
                <flux:select.option :value="$statusOption->value">{{ $statusOption->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="agencyId" :label="__('booking::reservations.fields.agency')">
            <flux:select.option value="">{{ __('booking::reservations.availability.all_agencies') }}</flux:select.option>
            @foreach ($agencies as $agency)
                <flux:select.option :value="$agency->id">{{ $agency->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input type="date" wire:model.live="startDate" :label="__('booking::reservations.fields.start_date')" />
        <flux:input type="date" wire:model.live="endDate" :label="__('booking::reservations.fields.end_date')" />
        <flux:checkbox wire:model.live="isInConflict" :label="__('booking::reservations.list.in_conflict_only')" />
    </div>

    <x-loading-hint />

    @if ($this->reservations->isEmpty())
        <x-empty-state :heading="__('booking::reservations.list.empty')" :description="__('booking::reservations.list.empty_help')">
            <x-slot:actions>
                <flux:button size="sm" wire:click="clearFilters">{{ __('screens.clear_filters') }}</flux:button>
                <flux:button size="sm" variant="ghost" wire:navigate :href="route('availability.index')">{{ __('booking::reservations.navigation.availability') }}</flux:button>
            </x-slot:actions>
        </x-empty-state>
    @else
        <flux:table :paginate="$this->reservations" wire:loading.class="opacity-50">
            <flux:table.columns>
                <flux:table.column>{{ __('booking::reservations.fields.reference') }}</flux:table.column>
                <flux:table.column>{{ __('booking::reservations.fields.customer') }}</flux:table.column>
                <flux:table.column>{{ __('booking::reservations.fields.period') }}</flux:table.column>
                <flux:table.column>{{ __('booking::reservations.fields.agency') }}</flux:table.column>
                <flux:table.column>{{ __('booking::reservations.fields.status') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->reservations as $reservation)
                    <flux:table.row wire:key="reservation-{{ $reservation->id }}">
                        <flux:table.cell variant="strong">{{ $reservation->machine->reference }}</flux:table.cell>
                        <flux:table.cell>{{ $reservation->customer->name }}</flux:table.cell>
                        <flux:table.cell>{{ $reservation->start_date->format('d/m/Y') }} → {{ $reservation->end_date->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $reservation->agency->name }}</flux:table.cell>
                        <flux:table.cell>
                            @include('booking::partials.reservation-status', ['reservation' => $reservation])
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button size="xs" variant="ghost" wire:navigate :href="route('reservations.show', $reservation)">
                                {{ __('booking::reservations.list.open') }}
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
