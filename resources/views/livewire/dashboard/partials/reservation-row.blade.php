<a
    href="{{ route('reservations.show', $reservation) }}"
    wire:navigate
    wire:key="{{ $rowKey }}-{{ $reservation->id }}"
    class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 rounded-lg px-3 py-2 hover:bg-zinc-100 focus-visible:outline-2 dark:hover:bg-zinc-700/50"
>
    <span class="flex min-w-0 flex-col">
        <flux:text variant="strong">{{ $reservation->machine->reference }} · {{ $reservation->machine->category->name }}</flux:text>
        <flux:text size="sm">
            {{ $reservation->customer->name }}@if ($isAllAgencies) · {{ $reservation->machine->agency->name }}@endif
        </flux:text>
    </span>
    <span class="flex flex-col items-end gap-1">
        <flux:text size="sm">{{ $reservation->start_date->format('d/m/Y') }} → {{ $reservation->end_date->format('d/m/Y') }}</flux:text>
        @if ($note !== null)
            <flux:badge size="sm" :color="$noteColor ?? 'amber'">{{ $note }}</flux:badge>
        @endif
    </span>
</a>
