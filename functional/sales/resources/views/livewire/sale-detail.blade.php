<div class="flex flex-col gap-6">
    <x-page-heading :title="__('sales::sales.detail.title', ['reference' => $sale->machine->reference])">
        <x-slot:actions>
            <flux:badge :color="$sale->status->color()">{{ $sale->status->label() }}</flux:badge>
            @if ($sale->state()->acceptsDescriptionChange())
                <flux:modal.trigger name="edit-listing">
                    <flux:button size="sm" icon="pencil-square">{{ __('sales::sales.detail.edit_listing') }}</flux:button>
                </flux:modal.trigger>
            @endif
        </x-slot:actions>
    </x-page-heading>

    @if (session('sale-flash'))
        <flux:callout variant="success" icon="check-circle" :heading="session('sale-flash')" />
    @endif

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <flux:card class="grid gap-4 md:grid-cols-3">
        <div>
            <flux:text size="sm">{{ __('sales::sales.fields.machine') }}</flux:text>
            <flux:heading>{{ $sale->machine->reference }} · {{ $sale->machine->category->name }}</flux:heading>
            <flux:text>{{ __('sales::sales.fields.home_agency') }} : {{ $sale->machine->agency->name }}</flux:text>
            <flux:badge size="sm" :color="$sale->machine->status->color()">{{ $sale->machine->status->label() }}</flux:badge>
        </div>
        <div>
            <flux:text size="sm">{{ __('sales::sales.fields.asking_price') }}</flux:text>
            <flux:heading>{{ $sale->asking_price->format() }} € HT</flux:heading>
            @if ($sale->final_price !== null)
                <flux:text>{{ __('sales::sales.fields.final_price') }} : {{ $sale->final_price->format() }} € HT</flux:text>
                <flux:text>{{ __('sales::sales.fields.buyer') }} : {{ $sale->buyer?->name }}</flux:text>
            @endif
        </div>
        <div>
            <flux:text size="sm">{{ __('sales::sales.detail.listed_by', ['agency' => $sale->agency->name, 'date' => $sale->created_at->format('d/m/Y')]) }}</flux:text>
            <flux:text>{{ __('sales::sales.fields.year_of_manufacture') }} : {{ $sale->year_of_manufacture ?? '—' }}</flux:text>
            <flux:text>{{ __('sales::sales.fields.operating_hours') }} : {{ $sale->operating_hours ?? '—' }}</flux:text>
            <flux:text>{{ __('sales::sales.fields.condition') }} : {{ $sale->condition }}</flux:text>
            @if ($sale->comment !== null)
                <flux:text>{{ $sale->comment }}</flux:text>
            @endif
        </div>
    </flux:card>

    <section class="flex flex-col gap-2">
        <x-section-heading level="3" :title="__('sales::sales.detail.history')" />
        <ul class="flex flex-col gap-1">
            @foreach ($this->history as $activity)
                <li wire:key="activity-{{ $activity->id }}" class="text-sm text-zinc-700 dark:text-zinc-200">
                    {{ $activity->created_at?->format('d/m/Y H:i') }} · {{ $activity->description }}
                    @if ($activity->causer !== null)
                        <span class="text-zinc-500 dark:text-zinc-400">({{ $activity->causer->getAttribute('name') }})</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>

    <flux:modal name="edit-listing" class="md:w-xl">
        <form wire:submit="updateListing" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ __('sales::sales.detail.edit_listing') }}</flux:heading>
            @include('sales::partials.listing-fields', ['isAskingPriceEditable' => $sale->state()->acceptsAskingPriceChange()])
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('sales::sales.form.back') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('sales::sales.detail.save_listing') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
