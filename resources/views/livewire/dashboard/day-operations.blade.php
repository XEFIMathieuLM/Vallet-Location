<section wire:poll.60s class="flex flex-col gap-12">
    @include('livewire.dashboard.partials.welcome-band')

    <div class="flex flex-col gap-5">
        <x-section-heading :title="__('dashboard.operations.heading')" />

        <div class="grid gap-4 lg:grid-cols-2">
            <x-tile class="gap-6">
                @include('livewire.dashboard.partials.tile-heading', ['label' => __('dashboard.operations.departures'), 'section' => $departures])
                <div class="flex flex-col">
                    @forelse ($departures->items as $reservation)
                        @include('livewire.dashboard.partials.reservation-row', [
                            'rowKey' => 'departure',
                            'note' => $reservation->start_date->lt($today) ? __('dashboard.operations.planned_on', ['date' => $reservation->start_date->format('d/m/Y')]) : null,
                        ])
                    @empty
                        <flux:text class="border-t border-black/10 pt-3 dark:border-white/16">{{ __('dashboard.operations.empty.departures') }}</flux:text>
                    @endforelse
                </div>
                @include('livewire.dashboard.partials.section-more', ['section' => $departures, 'url' => route('reservations.index', ['statut' => 'confirmed']), 'label' => __('dashboard.operations.see_all')])
            </x-tile>

            <x-tile class="gap-6">
                @include('livewire.dashboard.partials.tile-heading', ['label' => __('dashboard.operations.returns'), 'section' => $returns])
                <div class="flex flex-col">
                    @forelse ($returns->items as $reservation)
                        @include('livewire.dashboard.partials.reservation-row', ['rowKey' => 'return', 'note' => null])
                    @empty
                        <flux:text class="border-t border-black/10 pt-3 dark:border-white/16">{{ __('dashboard.operations.empty.returns') }}</flux:text>
                    @endforelse
                </div>
                @include('livewire.dashboard.partials.section-more', ['section' => $returns, 'url' => route('reservations.index', ['statut' => 'in_progress']), 'label' => __('dashboard.operations.see_all')])
            </x-tile>

            <x-tile :variant="$lateReturns->total > 0 ? 'accent' : 'soft'" class="gap-6">
                @include('livewire.dashboard.partials.tile-heading', ['label' => __('dashboard.operations.late_returns'), 'section' => $lateReturns])
                <div class="flex flex-col">
                    @forelse ($lateReturns->items as $reservation)
                        @include('livewire.dashboard.partials.reservation-row', [
                            'rowKey' => 'late-return',
                            'note' => trans_choice('dashboard.operations.days_late', (int) $reservation->end_date->diffInDays($today)),
                            'noteColor' => 'red',
                        ])
                    @empty
                        <flux:text class="border-t border-black/10 pt-3 dark:border-white/16">{{ __('dashboard.operations.empty.late_returns') }}</flux:text>
                    @endforelse
                </div>
                @include('livewire.dashboard.partials.section-more', ['section' => $lateReturns, 'url' => route('reservations.index', ['statut' => 'in_progress']), 'label' => __('dashboard.operations.see_all')])
            </x-tile>

            <x-tile class="gap-6">
                @include('livewire.dashboard.partials.tile-heading', ['label' => __('dashboard.operations.conflicts'), 'section' => $conflicts])
                <div class="flex flex-col">
                    @forelse ($conflicts->items as $reservation)
                        @include('livewire.dashboard.partials.reservation-row', ['rowKey' => 'conflict', 'note' => $reservation->conflict_reason?->label(), 'noteColor' => 'red'])
                    @empty
                        <flux:text class="border-t border-black/10 pt-3 dark:border-white/16">{{ __('dashboard.operations.empty.conflicts') }}</flux:text>
                    @endforelse
                </div>
                @include('livewire.dashboard.partials.section-more', ['section' => $conflicts, 'url' => route('reservations.index', ['statut' => 'confirmed', 'en-conflit' => 1]), 'label' => __('dashboard.operations.see_all')])
            </x-tile>
        </div>
    </div>

    @include('livewire.dashboard.partials.upcoming-days')
</section>
