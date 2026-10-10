<div class="flex flex-col gap-6">
    <x-page-heading :title="__('sales::sales.detail.title', ['reference' => $sale->machine->reference])">
        <x-slot:actions>
            <flux:badge :color="$sale->status->color()">{{ $sale->status->label() }}</flux:badge>
            <flux:button size="sm" variant="ghost" :href="route('sales.machine-history', $sale->machine)" wire:navigate>{{ __('sales::sales.machine_history.link') }}</flux:button>
            @if ($sale->state()->isOpen())
                <flux:modal.trigger name="cancel-sale"><flux:button size="sm" variant="ghost">{{ __('sales::sales.detail.cancel') }}</flux:button></flux:modal.trigger>
            @endif
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

    @if ($sale->status === \Functional\Sales\Enums\SaleStatus::Reserved)
        <flux:card class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading>{{ __('sales::sales.detail.reserved_for', ['buyer' => $sale->buyer?->name, 'price' => $sale->final_price?->format()]) }}</flux:heading>
                <flux:text>{{ __('sales::sales.detail.planned_handover', ['date' => $sale->planned_handover_date?->format('d/m/Y')]) }}</flux:text>
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:modal.trigger name="change-handover-date"><flux:button size="sm">{{ __('sales::sales.detail.change_handover_date') }}</flux:button></flux:modal.trigger>
                <flux:modal.trigger name="hand-over"><flux:button size="sm" variant="primary">{{ __('sales::sales.detail.hand_over') }}</flux:button></flux:modal.trigger>
                <flux:modal.trigger name="release-reservation"><flux:button size="sm" variant="ghost">{{ __('sales::sales.detail.release_reservation') }}</flux:button></flux:modal.trigger>
            </div>
        </flux:card>
    @endif

    @if ($sale->status === \Functional\Sales\Enums\SaleStatus::Sold)
        <flux:card class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading>{{ __('sales::sales.detail.sold_to', ['buyer' => $sale->buyer?->name, 'price' => $sale->final_price?->format(), 'date' => $sale->handed_over_on?->format('d/m/Y')]) }}</flux:heading>
                <flux:text>{{ __('sales::sales.detail.frozen') }}</flux:text>
            </div>
            @if ($this->transmission !== null)
                <flux:badge :color="$this->transmission->status->color()">{{ __('sales::sales.detail.transmission', ['status' => $this->transmission->status->label()]) }}</flux:badge>
            @endif
        </flux:card>
    @endif

    <livewire:sales.sale-offers :sale="$sale" :key="'offers-'.$sale->id.'-'.$sale->status->value" />

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

    <flux:modal name="change-handover-date" class="md:w-lg">
        <form wire:submit="changePlannedHandoverDate" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ __('sales::sales.detail.change_handover_date') }}</flux:heading>
            <flux:input type="date" wire:model="newPlannedHandoverDate" :label="__('sales::sales.offers.planned_handover_date')" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('sales::sales.form.back') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('sales::sales.detail.save_listing') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="cancel-sale" class="md:w-lg">
        <form wire:submit="cancelSale" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ __('sales::sales.detail.cancel') }}</flux:heading>
            <flux:text>{{ __('sales::sales.detail.cancel_help') }}</flux:text>
            <flux:input wire:model="cancellationReason" :label="__('sales::sales.detail.reason')" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('sales::sales.form.back') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="danger">{{ __('sales::sales.detail.cancel') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="hand-over" class="md:w-lg">
        <form wire:submit="handOver" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ __('sales::sales.detail.hand_over') }}</flux:heading>
            <flux:text>{{ __('sales::sales.detail.hand_over_help') }}</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('sales::sales.form.back') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('sales::sales.detail.hand_over') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="release-reservation" class="md:w-lg">
        <form wire:submit="releaseReservation" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ __('sales::sales.detail.release_reservation') }}</flux:heading>
            <flux:text>{{ __('sales::sales.detail.release_help') }}</flux:text>
            <flux:input wire:model="releaseReason" :label="__('sales::sales.detail.reason')" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('sales::sales.form.back') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="danger">{{ __('sales::sales.detail.release_reservation') }}</flux:button>
            </div>
        </form>
    </flux:modal>

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
