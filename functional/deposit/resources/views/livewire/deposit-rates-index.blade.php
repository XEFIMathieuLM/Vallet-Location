<div class="space-y-6">
    <x-page-heading :title="__('deposit::rates.title')" />
    <flux:text>{{ __('deposit::rates.intro') }}</flux:text>

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <form wire:submit="saveDefault" class="flex flex-wrap items-end gap-2">
        <flux:input wire:model="defaultAmount" inputmode="decimal" :label="__('deposit::rates.default_amount', ['amount' => $defaultRate?->amount->format()])" :placeholder="$defaultRate?->amount->format()" />
        <flux:button type="submit" variant="primary">{{ __('deposit::rates.save') }}</flux:button>
        <x-loading-hint wire:target="saveDefault" />
    </form>

    @if ($categories->isEmpty())
        <x-empty-state :heading="__('deposit::rates.empty_heading')" :description="__('deposit::rates.empty')" />
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('deposit::rates.category') }}</flux:table.column>
                <flux:table.column>{{ __('deposit::rates.amount') }}</flux:table.column>
                <flux:table.column />
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($categories as $category)
                    @php($ownRate = $ratesByCategory->get($category->id))
                    <flux:table.row :key="$category->id">
                        <flux:table.cell>{{ $category->name }}</flux:table.cell>
                        <flux:table.cell>
                            {{ __('deposit::section.amount', ['amount' => ($ownRate ?? $defaultRate)?->amount->format()]) }}
                            <flux:badge size="sm">{{ $ownRate ? __('deposit::rates.own') : __('deposit::rates.default') }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="sm" variant="ghost" :href="route('deposit.rates.edit', $category)" wire:navigate>{{ __('deposit::rates.edit') }}</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
