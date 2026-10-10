<div class="flex flex-col gap-6">
    <x-page-heading :title="__('portal::navigation.reservations')" />

    @if ($reservations === null)
        <x-empty-state :heading="__('portal::reservations.unattached')" :description="__('portal::reservations.unattached_help')" />
    @elseif ($reservations->isEmpty())
        <x-empty-state :heading="__('portal::reservations.empty')" />
    @else
        <div class="flex flex-col gap-4">
            @foreach ($reservations as $reservation)
                @php($customerStatus = \Functional\Portal\Enums\CustomerReservationStatus::fromReservationStatus($reservation->status))
                <flux:card wire:key="reservation-{{ $reservation->id }}" class="flex flex-col gap-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <flux:heading size="lg">{{ $reservation->machine->reference }} · {{ $reservation->machine->category->name }}</flux:heading>
                        <flux:badge size="sm" :color="$customerStatus->color()">{{ $customerStatus->label() }}</flux:badge>
                    </div>
                    <flux:text>{{ __('portal::reservations.period', ['start' => $reservation->start_date->format('d/m/Y'), 'end' => $reservation->end_date->format('d/m/Y')]) }}</flux:text>
                    <flux:text>{{ __('portal::reservations.pickup', ['agency' => $reservation->machine->agency->name]) }}@if ($reservation->machine->agency->address !== null) — {{ $reservation->machine->agency->address }}@endif</flux:text>
                    @if ($customerStatus === \Functional\Portal\Enums\CustomerReservationStatus::Confirmed)
                        <flux:text size="sm">{{ __('portal::reservations.contact_agency', ['agency' => $reservation->machine->agency->name]) }}</flux:text>
                    @endif
                </flux:card>
            @endforeach
        </div>
        <flux:pagination :paginator="$reservations" />
    @endif
</div>
