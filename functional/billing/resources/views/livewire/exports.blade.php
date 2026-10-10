<div class="space-y-6">
    <flux:heading size="xl">{{ __('billing::exports.title') }}</flux:heading>
    <flux:text>{{ __('billing::exports.intro') }}</flux:text>

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <flux:button variant="primary" icon="arrow-down-tray" wire:click="export" wire:confirm="{{ __('billing::exports.confirm') }}">
        {{ __('billing::exports.create') }}
    </flux:button>

    @if ($billingExports->isEmpty())
        <flux:text>{{ __('billing::exports.empty') }}</flux:text>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('billing::exports.date') }}</flux:table.column>
                <flux:table.column>{{ __('billing::exports.author') }}</flux:table.column>
                <flux:table.column>{{ __('billing::exports.lines') }}</flux:table.column>
                <flux:table.column />
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($billingExports as $billingExport)
                    <flux:table.row :key="$billingExport->id">
                        <flux:table.cell>{{ $billingExport->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                        <flux:table.cell>{{ $billingExport->creator->name }}</flux:table.cell>
                        <flux:table.cell>{{ trans_choice('billing::exports.line_count', $billingExport->line_count, ['count' => $billingExport->line_count]) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="sm" icon="document-arrow-down" :href="route('billing.exports.download', $billingExport)">{{ __('billing::exports.download') }}</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
