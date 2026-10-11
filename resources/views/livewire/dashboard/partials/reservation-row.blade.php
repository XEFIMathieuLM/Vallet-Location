<a
    href="{{ route('reservations.show', $reservation) }}"
    wire:navigate
    wire:key="{{ $rowKey }}-{{ $reservation->id }}"
    class="flex items-center gap-4 border-t border-black/10 py-3 hover:underline dark:border-white/16"
>
    <span class="flex size-14 shrink-0 items-center justify-center bg-white p-2 text-zinc-950 dark:bg-black dark:text-white">
        <x-fleet::pictogram :category="$reservation->machine->category->name" class="size-full" />
    </span>
    <span class="flex min-w-0 flex-1 flex-col">
        <span class="font-bold">{{ $reservation->machine->reference }} · {{ $reservation->machine->category->name }}</span>
        <span class="text-sm opacity-75">
            {{ $reservation->customer->name }}@if ($isAllAgencies) · {{ $reservation->machine->agency->name }}@endif
        </span>
    </span>
    <span class="flex shrink-0 flex-col items-end gap-1 text-end">
        <span class="text-xs whitespace-nowrap opacity-75">{{ $reservation->start_date->format('d/m/Y') }} → {{ $reservation->end_date->format('d/m/Y') }}</span>
        @if ($note !== null)
            <flux:badge size="sm" :color="$noteColor ?? 'amber'">{{ $note }}</flux:badge>
        @endif
    </span>
</a>
