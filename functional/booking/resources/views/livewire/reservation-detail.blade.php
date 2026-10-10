<div class="flex max-w-5xl flex-col gap-8">
    <x-page-heading :title="__('booking::reservations.detail.title', ['reference' => $reservation->machine->reference])">
        <x-slot:actions>
            @include('booking::partials.reservation-status', ['reservation' => $reservation])
        </x-slot:actions>
    </x-page-heading>

    @if (session('reservation-created'))
        <flux:callout variant="success" icon="check-circle" :heading="session('reservation-created')" />
    @endif

    @if ($reservation->conflict_reason !== null)
        <flux:callout variant="warning" icon="exclamation-triangle"
            :heading="__('booking::reservations.detail.in_conflict', ['reason' => $reservation->conflict_reason->label()])" />
    @endif

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <div>
        <dl class="grid border-t-2 border-zinc-950 sm:grid-cols-2 lg:grid-cols-4 dark:border-white">
            @foreach ([
                'machine' => "{$reservation->machine->reference} · {$reservation->machine->category->name}",
                'home_agency' => $reservation->machine->agency->name,
                'customer' => collect([$reservation->customer->name, $reservation->customer->phone, $reservation->customer->email])->filter()->implode(' · '),
                'period' => "{$reservation->start_date->format('d/m/Y')} → {$reservation->end_date->format('d/m/Y')}",
                'planned_end_date' => $reservation->planned_end_date->format('d/m/Y'),
                'created_by' => "{$reservation->author->name} ({$reservation->agency->name})",
                'departed_at' => $reservation->departed_at?->format('d/m/Y H:i') ?? '—',
                'returned_at' => $reservation->returned_at?->format('d/m/Y H:i') ?? '—',
            ] as $field => $fieldValue)
                <div class="flex flex-col gap-1 border-b border-zinc-100 py-4 sm:pe-6 dark:border-white/16">
                    <dt class="eyebrow text-zinc-500 dark:text-white/72">{{ __("booking::reservations.fields.{$field}") }}</dt>
                    <dd class="font-semibold">{{ $fieldValue }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    <div class="flex flex-wrap gap-2">
        @if ($isConfirmed)
            <flux:button variant="primary" wire:click="depart" :disabled="! $this->isReadyFor(\Functional\Booking\Enums\ReservationTransition::Departure)">
                {{ __('booking::reservations.transitions.departure') }}
            </flux:button>
            <flux:modal.trigger name="cancel-reservation">
                <flux:button variant="danger">{{ __('booking::reservations.transitions.cancellation') }}</flux:button>
            </flux:modal.trigger>

            <flux:modal name="cancel-reservation" class="max-w-md">
                <div class="flex flex-col gap-6">
                    <div class="flex flex-col gap-2">
                        <x-section-heading :title="__('booking::reservations.detail.cancel_heading')" />
                        <flux:text>{{ __('booking::reservations.detail.cancel_confirmation', ['reference' => $reservation->machine->reference, 'start' => $reservation->start_date->format('d/m/Y'), 'end' => $reservation->end_date->format('d/m/Y')]) }}</flux:text>
                    </div>
                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">{{ __('booking::reservations.detail.keep_reservation') }}</flux:button>
                        </flux:modal.close>
                        <flux:button variant="danger" wire:click="cancel">{{ __('booking::reservations.detail.confirm_cancellation') }}</flux:button>
                    </div>
                </div>
            </flux:modal>
        @endif

        @if ($isInProgress)
            @foreach ($returnConditions as $returnCondition)
                <flux:button :variant="$loop->first ? 'primary' : 'filled'" wire:click="returnMachine('{{ $returnCondition->value }}')"
                    :disabled="! $this->isReadyFor(\Functional\Booking\Enums\ReservationTransition::Return)">
                    {{ __('booking::reservations.detail.return_as', ['condition' => $returnCondition->label()]) }}
                </flux:button>
            @endforeach
        @endif
    </div>

    <div class="flex flex-col gap-10" data-reservation-sections>
        @foreach ($sections as $section)
            <livewire:dynamic-component :component="$section" :reservation="$reservation" :key="'section-'.$section" />
        @endforeach
    </div>
</div>
