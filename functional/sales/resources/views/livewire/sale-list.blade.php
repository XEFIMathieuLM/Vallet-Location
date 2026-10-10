<div class="space-y-6">
    <x-page-heading :title="__('sales::sales.list.title')">
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" :href="route('sales.create')" wire:navigate>{{ __('sales::sales.list.new') }}</flux:button>
        </x-slot:actions>
    </x-page-heading>

    @if (session('sale-flash'))
        <flux:callout variant="success" icon="check-circle" :heading="session('sale-flash')" />
    @endif

    <div class="grid gap-4 md:grid-cols-4">
        <flux:select wire:model.live="status" :label="__('sales::sales.fields.status')">
            <flux:select.option value="">{{ __('sales::sales.list.all') }}</flux:select.option>
            @foreach ($statuses as $saleStatus)
                <flux:select.option :value="$saleStatus->value">{{ $saleStatus->label() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="categoryId" :label="__('sales::sales.fields.category')">
            <flux:select.option value="">{{ __('sales::sales.list.all') }}</flux:select.option>
            @foreach ($categories as $category)
                <flux:select.option :value="$category->id">{{ $category->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="agencyId" :label="__('sales::sales.fields.home_agency')">
            <flux:select.option value="">{{ __('sales::sales.list.all') }}</flux:select.option>
            @foreach ($agencies as $agency)
                <flux:select.option :value="$agency->id">{{ $agency->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <div class="flex items-end">
            <flux:button variant="ghost" wire:click="clearFilters">{{ __('sales::sales.list.clear_filters') }}</flux:button>
        </div>
    </div>

    <x-loading-hint />

    @if ($this->sales->isEmpty())
        <x-empty-state :heading="__('sales::sales.list.empty_heading')" :description="__('sales::sales.list.empty')" />
    @else
        <flux:table :paginate="$this->sales">
            <flux:table.columns>
                <flux:table.column>{{ __('sales::sales.fields.machine') }}</flux:table.column>
                <flux:table.column>{{ __('sales::sales.fields.home_agency') }}</flux:table.column>
                <flux:table.column>{{ __('sales::sales.fields.fleet_status') }}</flux:table.column>
                <flux:table.column>{{ __('sales::sales.fields.status') }}</flux:table.column>
                <flux:table.column align="end">{{ __('sales::sales.fields.asking_price') }}</flux:table.column>
                <flux:table.column align="end">{{ __('sales::sales.fields.final_price') }}</flux:table.column>
                <flux:table.column>{{ __('sales::sales.fields.buyer') }}</flux:table.column>
                <flux:table.column>{{ __('sales::sales.fields.handover') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->sales as $sale)
                    <flux:table.row :key="$sale->id">
                        <flux:table.cell variant="strong">
                            <flux:link :href="route('sales.show', $sale)" wire:navigate>{{ $sale->machine->reference }}</flux:link>
                            <span class="block text-xs font-normal text-zinc-600 dark:text-zinc-300">{{ $sale->machine->category->name }}</span>
                        </flux:table.cell>
                        <flux:table.cell>{{ $sale->machine->agency->name }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$sale->machine->status->color()">{{ $sale->machine->status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$sale->status->color()">{{ $sale->status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell align="end">{{ $sale->asking_price->format() }} €</flux:table.cell>
                        <flux:table.cell align="end">{{ $sale->final_price !== null ? $sale->final_price->format().' €' : '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $sale->buyer?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ ($sale->handed_over_on ?? $sale->planned_handover_date)?->format('d/m/Y') ?? '—' }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
