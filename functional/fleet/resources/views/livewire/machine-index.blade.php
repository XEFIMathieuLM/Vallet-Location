<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl" level="1">{{ __('fleet::machines.index.title') }}</flux:heading>
        <div class="flex gap-2">
            <flux:button icon="arrow-up-tray" wire:navigate :href="route('machines.import')">{{ __('fleet::machines.import.title') }}</flux:button>
            <flux:button variant="primary" icon="plus" wire:navigate :href="route('machines.create')">{{ __('fleet::machines.index.create') }}</flux:button>
        </div>
    </div>

    @if (session('machine-saved'))
        <flux:callout variant="success" icon="check-circle" :heading="session('machine-saved')" />
    @endif

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <div class="grid gap-4 md:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="referenceSearch" icon="magnifying-glass" :label="__('fleet::machines.fields.reference')" />
        <flux:select wire:model.live="categoryId" :label="__('fleet::machines.fields.category')">
            <flux:select.option value="">{{ __('fleet::machines.index.all') }}</flux:select.option>
            @foreach ($categories as $category)
                <flux:select.option :value="$category->id">{{ $category->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="agencyId" :label="__('fleet::machines.fields.agency')">
            <flux:select.option value="">{{ __('fleet::machines.index.all') }}</flux:select.option>
            @foreach ($agencies as $agency)
                <flux:select.option :value="$agency->id">{{ $agency->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="status" :label="__('fleet::machines.fields.status')">
            <flux:select.option value="">{{ __('fleet::machines.index.all') }}</flux:select.option>
            @foreach ($statuses as $statusOption)
                <flux:select.option :value="$statusOption->value">{{ $statusOption->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table :paginate="$this->machines">
        <flux:table.columns>
            <flux:table.column>{{ __('fleet::machines.fields.reference') }}</flux:table.column>
            <flux:table.column>{{ __('fleet::machines.fields.category') }}</flux:table.column>
            <flux:table.column>{{ __('fleet::machines.fields.agency') }}</flux:table.column>
            <flux:table.column>{{ __('fleet::machines.fields.status') }}</flux:table.column>
            <flux:table.column>{{ __('fleet::machines.fields.vgp_due_date') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->machines as $machine)
                <flux:table.row wire:key="machine-{{ $machine->id }}">
                    <flux:table.cell variant="strong">{{ $machine->reference }}</flux:table.cell>
                    <flux:table.cell>{{ $machine->category->name }}</flux:table.cell>
                    <flux:table.cell>{{ $machine->agency->name }}</flux:table.cell>
                    <flux:table.cell><flux:badge size="sm" :color="$machine->status->color()">{{ $machine->status->label() }}</flux:badge></flux:table.cell>
                    <flux:table.cell>
                        @if ($machine->is_subject_to_vgp)
                            {{ $machine->vgp_due_date?->format('d/m/Y') ?? __('fleet::machines.index.vgp_missing') }}
                        @else
                            —
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:dropdown position="bottom" align="end">
                            <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" :aria-label="__('fleet::machines.index.actions')" />
                            <flux:menu>
                                <flux:menu.item icon="pencil-square" wire:navigate :href="route('machines.edit', $machine)">{{ __('fleet::machines.index.edit') }}</flux:menu.item>
                                @foreach (\Functional\Fleet\Enums\MachineTransition::manualFrom($machine->status) as $transition)
                                    @if ($transition === \Functional\Fleet\Enums\MachineTransition::Retire)
                                        <flux:menu.item variant="danger" wire:click="applyTransition({{ $machine->id }}, '{{ $transition->value }}')" wire:confirm="{{ __('fleet::machines.index.retire_confirmation') }}">
                                            {{ $transition->label() }}
                                        </flux:menu.item>
                                    @else
                                        <flux:menu.item wire:click="applyTransition({{ $machine->id }}, '{{ $transition->value }}')">
                                            {{ $transition->label() }}
                                        </flux:menu.item>
                                    @endif
                                @endforeach
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
