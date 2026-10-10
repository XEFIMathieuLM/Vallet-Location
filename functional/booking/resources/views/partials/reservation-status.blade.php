<div class="flex flex-wrap gap-1">
    <flux:badge size="sm" :color="$reservation->status->color()">{{ $reservation->status->label() }}</flux:badge>
    @if ($reservation->conflict_reason !== null)
        <flux:badge size="sm" color="red" icon="exclamation-triangle">{{ $reservation->conflict_reason->label() }}</flux:badge>
    @endif
</div>
