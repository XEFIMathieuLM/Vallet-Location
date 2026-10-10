<section class="flex flex-col gap-6">
    <flux:heading size="xl">{{ __('inspection::damages.list.title') }}</flux:heading>

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    @forelse ($reservationsDamages as $damages)
        @php($reservation = $damages->first()->reservation)
        <div wire:key="reinvoice-{{ $reservation->id }}" class="flex flex-col gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="flex flex-col">
                    <flux:heading>{{ $reservation->machine->reference }} – {{ $reservation->customer->name }}</flux:heading>
                    <flux:text size="sm">{{ __('inspection::damages.list.agency', ['agency' => $reservation->agency->name]) }}</flux:text>
                </div>
                <flux:button size="sm" icon="arrows-right-left" :href="route('inspection.comparison', $reservation)" wire:navigate>
                    {{ __('inspection::panel.compare') }}
                </flux:button>
            </div>
            @foreach ($damages as $damage)
                <div wire:key="reinvoice-damage-{{ $damage->id }}" class="flex flex-wrap items-center justify-between gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                    <div class="flex flex-col">
                        <flux:text variant="strong">{{ $damage->view->label }} : {{ $damage->comment }}</flux:text>
                        <flux:text size="sm">{{ __('inspection::damages.reported', ['author' => $damage->reporter->name, 'date' => $damage->reported_at->format('d/m/Y H:i')]) }}</flux:text>
                    </div>
                    @include('inspection::partials.damage-actions', ['damage' => $damage])
                </div>
            @endforeach
        </div>
    @empty
        <flux:text>{{ __('inspection::damages.list.empty') }}</flux:text>
    @endforelse
</section>
