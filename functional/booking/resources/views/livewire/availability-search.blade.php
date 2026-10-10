<div class="flex flex-col gap-6">
    <flux:heading size="xl" level="1">{{ __('booking::reservations.availability.title') }}</flux:heading>

    <div class="grid gap-4 md:grid-cols-4">
        <flux:select wire:model.live="categoryId" :label="__('booking::reservations.fields.category')">
            <flux:select.option value="">{{ __('booking::reservations.availability.all_categories') }}</flux:select.option>
            @foreach ($categories as $category)
                <flux:select.option :value="$category->id">{{ $category->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="agencyId" :label="__('booking::reservations.fields.home_agency')">
            <flux:select.option value="">{{ __('booking::reservations.availability.all_agencies') }}</flux:select.option>
            @foreach ($agencies as $agency)
                <flux:select.option :value="$agency->id">{{ $agency->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input type="date" wire:model.live="startDate" :label="__('booking::reservations.fields.start_date')" />
        <flux:input type="date" wire:model.live="endDate" :label="__('booking::reservations.fields.end_date')" />
    </div>

    @if (session('reservation-created'))
        <flux:callout variant="success" icon="check-circle" :heading="session('reservation-created')" />
    @endif

    @if (! $this->hasValidPeriod)
        <flux:callout variant="warning" icon="exclamation-triangle" :heading="__('booking::reservations.availability.invalid_period')" />
    @elseif ($this->machines->isEmpty())
        <flux:text>{{ __('booking::reservations.availability.no_machine') }}</flux:text>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('booking::reservations.fields.reference') }}</flux:table.column>
                <flux:table.column>{{ __('booking::reservations.fields.category') }}</flux:table.column>
                <flux:table.column>{{ __('booking::reservations.fields.home_agency') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->machines as $machine)
                    <flux:table.row wire:key="machine-{{ $machine->id }}">
                        <flux:table.cell variant="strong">{{ $machine->reference }}</flux:table.cell>
                        <flux:table.cell>{{ $machine->category->name }}</flux:table.cell>
                        <flux:table.cell>{{ $machine->agency->name }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button size="sm" variant="primary" wire:navigate
                                :href="route('reservations.create', ['machine' => $machine->id, 'du' => $startDate, 'au' => $endDate])">
                                {{ __('booking::reservations.availability.reserve') }}
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
