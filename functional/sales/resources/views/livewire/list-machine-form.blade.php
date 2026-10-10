<div class="flex max-w-2xl flex-col gap-6">
    <x-page-heading :title="__('sales::sales.form.title')" />

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <form wire:submit="save" class="flex flex-col gap-4">
        <div class="grid gap-4 md:grid-cols-2">
            <flux:input wire:model.live.debounce.300ms="machineSearch" icon="magnifying-glass" :label="__('sales::sales.form.machine_search')" />
            <flux:select wire:model="machineId" :label="__('sales::sales.fields.machine')">
                <flux:select.option value="">{{ __('sales::sales.form.choose_machine') }}</flux:select.option>
                @foreach ($this->machines as $machine)
                    <flux:select.option :value="$machine->id">{{ $machine->reference }} · {{ $machine->category->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @include('sales::partials.listing-fields', ['isAskingPriceEditable' => true])

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('sales::sales.form.confirm') }}</flux:button>
            <flux:button :href="route('sales.index')" wire:navigate variant="ghost">{{ __('sales::sales.form.back') }}</flux:button>
        </div>
    </form>
</div>
