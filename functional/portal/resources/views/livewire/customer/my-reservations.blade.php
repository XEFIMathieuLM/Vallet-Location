<div class="flex flex-col">
    <x-portal::hero :title="__('portal::navigation.reservations')" />

    <div class="mx-auto flex w-full max-w-[90rem] flex-col gap-6 px-4 py-12 sm:px-10">

    @if ($reservations === null)
        <x-empty-state :heading="__('portal::reservations.unattached')" :description="__('portal::reservations.unattached_help')" />
    @elseif ($reservations->isEmpty())
        <x-empty-state :heading="__('portal::reservations.empty')" />
    @else
        <div class="flex flex-col border-t-2 border-zinc-950">
            @foreach ($reservations as $reservation)
                @php($customerStatus = \Functional\Portal\Enums\CustomerReservationStatus::fromReservationStatus($reservation->status))
                <article wire:key="reservation-{{ $reservation->id }}" class="flex flex-col gap-2 border-b border-zinc-100 py-6">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="heading-md flex items-center gap-3" data-flux-heading>
                            <x-fleet::pictogram :category="$reservation->machine->category->name" class="h-6 w-9 shrink-0" />
                            {{ $reservation->machine->reference }} · {{ $reservation->machine->category->name }}
                        </h2>
                        <flux:badge size="sm" :color="$customerStatus->color()">{{ $customerStatus->label() }}</flux:badge>
                    </div>
                    <flux:text>{{ __('portal::reservations.period', ['start' => $reservation->start_date->format('d/m/Y'), 'end' => $reservation->end_date->format('d/m/Y')]) }}</flux:text>
                    <flux:text>{{ __('portal::reservations.pickup', ['agency' => $reservation->machine->agency->name]) }}@if ($reservation->machine->agency->address !== null) — {{ $reservation->machine->agency->address }}@endif</flux:text>
                    @if ($reservation->machine->is_subject_to_vgp && $customerStatus !== \Functional\Portal\Enums\CustomerReservationStatus::Cancelled)
                        @if (array_key_exists($reservation->id, $deliveredReportIds))
                            <div>
                                <flux:button size="sm" icon="arrow-down-tray" :href="route('portal.reservations.certificate', $reservation)">{{ __('portal::reservations.download_certificate') }}</flux:button>
                            </div>
                        @else
                            <flux:text size="sm">{{ __('portal::reservations.certificate_not_available') }}</flux:text>
                        @endif
                    @endif
                    @if ($customerStatus === \Functional\Portal\Enums\CustomerReservationStatus::Confirmed)
                        <flux:text size="sm">{{ __('portal::reservations.contact_agency', ['agency' => $reservation->machine->agency->name]) }}</flux:text>
                    @endif
                </article>
            @endforeach
        </div>
        <flux:pagination :paginator="$reservations" />
    @endif
    </div>
</div>
