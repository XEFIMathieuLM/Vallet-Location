<div class="flex flex-col gap-5">
    <x-section-heading :title="__('dashboard.operations.upcoming_departures', ['days' => $upcomingDays])" />

    @if ($upcomingDepartures->items->isEmpty())
        <flux:text>{{ __('dashboard.operations.empty.upcoming_departures', ['days' => $upcomingDays]) }}</flux:text>
    @endif

    <ol class="grid border-t-2 border-zinc-950 lg:grid-cols-(--upcoming-columns) dark:border-white" style="--upcoming-columns: repeat({{ count($upcomingDayList->days) }}, minmax(0, 1fr))">
        @foreach ($upcomingDayList->days as $upcomingDay)
            <li wire:key="upcoming-day-{{ $upcomingDay->date->toDateString() }}" data-upcoming-day="{{ $upcomingDay->date->toDateString() }}" class="flex flex-col gap-2 border-b border-zinc-100 py-3 lg:min-h-40 lg:border-b-0 lg:border-s lg:px-3 lg:first:border-s-0 lg:first:ps-0 dark:border-white/16">
                <p class="flex items-baseline gap-2">
                    <span class="sr-only">{{ $upcomingDay->date->locale('fr')->isoFormat('dddd D MMMM') }}</span>
                    <span aria-hidden="true" class="text-sm text-zinc-500 first-letter:uppercase dark:text-white/72">{{ $upcomingDay->date->locale('fr')->isoFormat('ddd') }}</span>
                    <span aria-hidden="true" class="heading-lg">{{ $upcomingDay->date->format('j') }}</span>
                </p>
                @forelse ($upcomingDay->reservations as $reservation)
                    <a href="{{ route('reservations.show', $reservation) }}" wire:navigate wire:key="upcoming-{{ $reservation->id }}" class="flex items-center gap-3 bg-zinc-50 p-3 text-sm hover:bg-zinc-100 lg:flex-col lg:items-start dark:bg-zinc-900 dark:hover:bg-zinc-800">
                        <x-fleet::pictogram :category="$reservation->machine->category->name" class="h-8 w-12 shrink-0" />
                        <span class="flex min-w-0 flex-col">
                            <span class="font-bold">{{ $reservation->machine->reference }}</span>
                            <span class="text-zinc-500 dark:text-white/72">{{ $reservation->customer->name }}@if ($isAllAgencies) · {{ $reservation->machine->agency->name }}@endif</span>
                        </span>
                    </a>
                @empty
                    <p class="text-sm text-zinc-500 dark:text-white/72"><span aria-hidden="true">—</span><span class="sr-only">{{ __('dashboard.upcoming.no_departure') }}</span></p>
                @endforelse
            </li>
        @endforeach
    </ol>

    @include('livewire.dashboard.partials.section-more', ['section' => $upcomingDepartures, 'url' => route('reservations.index', ['statut' => 'confirmed']), 'label' => __('dashboard.operations.see_all')])
</div>
