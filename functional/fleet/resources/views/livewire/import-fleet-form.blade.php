<div class="flex max-w-3xl flex-col gap-6">
    <flux:heading size="xl" level="1">{{ __('fleet::machines.import.title') }}</flux:heading>
    <flux:text>{{ __('fleet::machines.import.format_help') }}</flux:text>

    <form wire:submit="import" class="flex flex-col gap-4">
        <flux:input type="file" wire:model="fleetFile" accept=".csv,.xlsx" :label="__('fleet::machines.import.file')" />
        <div class="flex gap-2">
            <flux:button type="submit" variant="primary" icon="arrow-up-tray">{{ __('fleet::machines.import.submit') }}</flux:button>
            <flux:button variant="ghost" wire:navigate :href="route('machines.index')">{{ __('fleet::machines.form.back') }}</flux:button>
        </div>
    </form>

    @if ($createdCount !== null)
        <flux:callout variant="success" icon="check-circle" :heading="trans_choice('fleet::machines.import.created', $createdCount)" />

        @if ($rejections !== [])
            <flux:heading size="lg">{{ __('fleet::machines.import.rejected_lines') }} ({{ count($rejections) }})</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('fleet::machines.import.line') }}</flux:table.column>
                    <flux:table.column>{{ __('fleet::machines.fields.reference') }}</flux:table.column>
                    <flux:table.column>{{ __('fleet::machines.import.reason') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($rejections as $rejection)
                        <flux:table.row wire:key="rejection-{{ $rejection['line'] }}">
                            <flux:table.cell>{{ $rejection['line'] }}</flux:table.cell>
                            <flux:table.cell>{{ $rejection['reference'] !== '' ? $rejection['reference'] : '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $rejection['reason'] }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    @endif
</div>
