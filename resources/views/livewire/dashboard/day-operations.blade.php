<section wire:poll.60s class="flex flex-col gap-4">
    <x-section-heading :title="__('dashboard.operations.heading')" />

    <div class="grid gap-4 lg:grid-cols-2">
        <flux:card class="flex flex-col gap-3">
            <x-section-heading level="3" :title="__('dashboard.operations.departures')" />
            @forelse ($departures->items as $reservation)
                @include('livewire.dashboard.partials.reservation-row', [
                    'rowKey' => 'departure',
                    'note' => $reservation->start_date->lt($today) ? __('dashboard.operations.planned_on', ['date' => $reservation->start_date->format('d/m/Y')]) : null,
                ])
            @empty
                <flux:text>{{ __('dashboard.operations.empty.departures') }}</flux:text>
            @endforelse
            @include('livewire.dashboard.partials.section-more', ['section' => $departures, 'url' => route('reservations.index', ['statut' => 'confirmed']), 'label' => __('dashboard.operations.see_all')])
        </flux:card>

        <flux:card class="flex flex-col gap-3">
            <x-section-heading level="3" :title="__('dashboard.operations.returns')" />
            @forelse ($returns->items as $reservation)
                @include('livewire.dashboard.partials.reservation-row', ['rowKey' => 'return', 'note' => null])
            @empty
                <flux:text>{{ __('dashboard.operations.empty.returns') }}</flux:text>
            @endforelse
            @include('livewire.dashboard.partials.section-more', ['section' => $returns, 'url' => route('reservations.index', ['statut' => 'in_progress']), 'label' => __('dashboard.operations.see_all')])
        </flux:card>

        <flux:card class="flex flex-col gap-3">
            <x-section-heading level="3" :title="__('dashboard.operations.late_returns')" />
            @forelse ($lateReturns->items as $reservation)
                @include('livewire.dashboard.partials.reservation-row', [
                    'rowKey' => 'late-return',
                    'note' => trans_choice('dashboard.operations.days_late', (int) $reservation->end_date->diffInDays($today)),
                    'noteColor' => 'red',
                ])
            @empty
                <flux:text>{{ __('dashboard.operations.empty.late_returns') }}</flux:text>
            @endforelse
            @include('livewire.dashboard.partials.section-more', ['section' => $lateReturns, 'url' => route('reservations.index', ['statut' => 'in_progress']), 'label' => __('dashboard.operations.see_all')])
        </flux:card>

        <flux:card class="flex flex-col gap-3">
            <x-section-heading level="3" :title="__('dashboard.operations.conflicts')" />
            @forelse ($conflicts->items as $reservation)
                @include('livewire.dashboard.partials.reservation-row', ['rowKey' => 'conflict', 'note' => $reservation->conflict_reason?->label(), 'noteColor' => 'red'])
            @empty
                <flux:text>{{ __('dashboard.operations.empty.conflicts') }}</flux:text>
            @endforelse
            @include('livewire.dashboard.partials.section-more', ['section' => $conflicts, 'url' => route('reservations.index', ['statut' => 'confirmed', 'en-conflit' => 1]), 'label' => __('dashboard.operations.see_all')])
        </flux:card>

        <flux:card class="flex flex-col gap-3 lg:col-span-2">
            <x-section-heading level="3" :title="__('dashboard.operations.upcoming_departures', ['days' => $upcomingDays])" />
            @forelse ($upcomingDepartures->items->groupBy(fn ($reservation) => $reservation->start_date->toDateString()) as $startDate => $reservationsOfDay)
                <flux:text variant="strong" class="mt-2 px-3 first-letter:uppercase">{{ \Carbon\CarbonImmutable::parse($startDate)->isoFormat('dddd D MMMM') }}</flux:text>
                @foreach ($reservationsOfDay as $reservation)
                    @include('livewire.dashboard.partials.reservation-row', ['rowKey' => 'upcoming', 'note' => null])
                @endforeach
            @empty
                <flux:text>{{ __('dashboard.operations.empty.upcoming_departures', ['days' => $upcomingDays]) }}</flux:text>
            @endforelse
            @include('livewire.dashboard.partials.section-more', ['section' => $upcomingDepartures, 'url' => route('reservations.index', ['statut' => 'confirmed']), 'label' => __('dashboard.operations.see_all')])
        </flux:card>
    </div>
</section>
