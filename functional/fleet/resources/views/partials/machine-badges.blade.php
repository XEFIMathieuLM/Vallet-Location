@foreach ($badges as $badge)
    @if ($badge->url !== null)
        <flux:badge size="sm" :color="$badge->color" :href="$badge->url" wire:navigate>{{ $badge->label }}</flux:badge>
    @else
        <flux:badge size="sm" :color="$badge->color">{{ $badge->label }}</flux:badge>
    @endif
@endforeach
