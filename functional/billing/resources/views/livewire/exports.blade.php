<div class="space-y-6">
    <x-page-heading :title="__('billing::exports.title')" />
    <flux:text>{{ __('billing::exports.intro') }}</flux:text>

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <div class="flex flex-wrap items-center gap-4">
        <flux:modal.trigger name="create-billing-export">
            <flux:button variant="primary" icon="arrow-down-tray">{{ __('billing::exports.create') }}</flux:button>
        </flux:modal.trigger>
        <x-loading-hint wire:target="export" />
    </div>

    <flux:modal name="create-billing-export" class="max-w-md">
        <div class="flex flex-col gap-6">
            <div class="flex flex-col gap-2">
                <x-section-heading :title="__('billing::exports.create')" />
                <flux:text>{{ __('billing::exports.confirm') }}</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('billing::damages.cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="export" x-on:click="$flux.modal('create-billing-export').close()">{{ __('billing::exports.confirm_create') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    @if ($billingExports->isEmpty())
        <x-empty-state :heading="__('billing::exports.empty_heading')" :description="__('billing::exports.empty')" />
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
                            <flux:button size="xs" icon="document-arrow-down" :href="route('billing.exports.download', $billingExport)">{{ __('billing::exports.download') }}</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
