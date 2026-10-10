<div class="flex flex-col gap-6">
    <x-page-heading :title="__('sales::sales.machine_history.title', ['reference' => $machine->reference])" />

    @if ($sales->isEmpty())
        <x-empty-state :heading="__('sales::sales.machine_history.empty_heading')" :description="__('sales::sales.machine_history.empty')" />
    @endif

    @foreach ($sales as $sale)
        <flux:card wire:key="sale-{{ $sale->id }}" class="flex flex-col gap-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <flux:link :href="route('sales.show', $sale)" wire:navigate>{{ __('sales::sales.machine_history.sale_of', ['date' => $sale->created_at->format('d/m/Y'), 'agency' => $sale->agency->name]) }}</flux:link>
                <flux:badge :color="$sale->status->color()">{{ $sale->status->label() }}</flux:badge>
            </div>
            <flux:text>{{ __('sales::sales.fields.asking_price') }} : {{ $sale->asking_price->format() }} €
                @if ($sale->final_price !== null) · {{ __('sales::sales.fields.final_price') }} : {{ $sale->final_price->format() }} € ({{ $sale->buyer?->name }}) @endif
                @if ($sale->cancellation_reason !== null) · {{ __('sales::sales.detail.reason') }} : {{ $sale->cancellation_reason }} @endif
            </flux:text>
            @if ($sale->offers->isNotEmpty())
                <ul class="text-sm text-zinc-700 dark:text-zinc-200">
                    @foreach ($sale->offers as $offer)
                        <li wire:key="offer-{{ $offer->id }}">{{ $offer->offered_on->format('d/m/Y') }} · {{ $offer->customer->name }} · {{ $offer->amount->format() }} € · {{ $offer->status->label() }}</li>
                    @endforeach
                </ul>
            @endif
            <ul class="text-sm text-zinc-500 dark:text-zinc-400">
                @foreach ($activities[$sale->id] ?? [] as $activity)
                    <li wire:key="activity-{{ $activity->id }}">{{ $activity->created_at?->format('d/m/Y H:i') }} · {{ $activity->description }}</li>
                @endforeach
            </ul>
        </flux:card>
    @endforeach
</div>
