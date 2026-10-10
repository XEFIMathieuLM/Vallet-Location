<div class="space-y-6">
    <x-page-heading :title="__('portal::prices.title')" />
    <flux:text>{{ __('portal::prices.intro') }}</flux:text>

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('portal::prices.category') }}</flux:table.column>
            <flux:table.column>{{ __('portal::prices.current') }}</flux:table.column>
            <flux:table.column>{{ __('portal::prices.new_price') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($categories as $category)
                @php($indicativePrice = $pricesByCategory->get($category->id))
                <flux:table.row :key="$category->id">
                    <flux:table.cell variant="strong">{{ $category->name }}</flux:table.cell>
                    <flux:table.cell>
                        {{ $indicativePrice !== null ? __('portal::prices.per_day', ['amount' => $priceFormatter->amount($indicativePrice->daily_price_cents)]) : '—' }}
                        @if ($indicativePrice !== null)
                            <flux:text size="sm">{{ __('portal::prices.changed_by', ['author' => $indicativePrice->author?->getAttribute('name'), 'date' => $indicativePrice->updated_at->timezone('Europe/Paris')->format('d/m/Y')]) }}</flux:text>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <form wire:submit="save({{ $category->id }})" class="flex items-end gap-2">
                            <flux:input size="sm" wire:model="amounts.{{ $category->id }}" :aria-label="__('portal::prices.new_price')" placeholder="95,00" class="w-24" />
                            <flux:button type="submit" size="sm">{{ __('portal::prices.save') }}</flux:button>
                        </form>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        @if ($indicativePrice !== null)
                            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="remove({{ $category->id }})">{{ __('portal::prices.remove') }}</flux:button>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
