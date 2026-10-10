<div class="space-y-6">
    <x-page-heading :title="__('deposit::pending.title')" />
    <flux:text>{{ __('deposit::pending.intro', ['days' => config('deposit.overdue_after_days')]) }}</flux:text>

    <div class="grid gap-4 sm:grid-cols-2 lg:max-w-2xl">
        <flux:select wire:model.live="agencyId" :label="__('deposit::pending.agency')">
            <flux:select.option value="">{{ __('deposit::pending.all_agencies') }}</flux:select.option>
            @foreach ($agencies as $agency)
                <flux:select.option :value="$agency->id">{{ $agency->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="status" :label="__('deposit::pending.status')">
            <flux:select.option value="">{{ __('deposit::pending.all_statuses') }}</flux:select.option>
            @foreach ($awaitingStatuses as $awaitingStatus)
                <flux:select.option :value="$awaitingStatus->value">{{ $awaitingStatus->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <x-loading-hint />

    @if ($deposits->isEmpty())
        <x-empty-state :heading="__('deposit::pending.empty_heading')" :description="__('deposit::pending.empty')" />
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('deposit::pending.reservation') }}</flux:table.column>
                <flux:table.column>{{ __('deposit::pending.customer') }}</flux:table.column>
                <flux:table.column>{{ __('deposit::pending.agency') }}</flux:table.column>
                <flux:table.column>{{ __('deposit::pending.amount') }}</flux:table.column>
                <flux:table.column>{{ __('deposit::pending.status') }}</flux:table.column>
                <flux:table.column>{{ __('deposit::pending.since') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($deposits as $deposit)
                    <flux:table.row :key="$deposit->id">
                        <flux:table.cell>
                            <flux:link :href="route('reservations.show', $deposit->reservation)" wire:navigate>{{ $deposit->reservation->machine->reference }}</flux:link>
                        </flux:table.cell>
                        <flux:table.cell>{{ $deposit->reservation->customer->name }}</flux:table.cell>
                        <flux:table.cell>{{ $deposit->reservation->machine->agency->name }}</flux:table.cell>
                        <flux:table.cell>{{ __('deposit::section.amount', ['amount' => $deposit->amount->format()]) }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm">{{ $deposit->status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell>
                            {{ $deposit->awaiting_since?->timezone(config('deposit.timezone'))->format('d/m/Y') }}
                            @if ($pendingDeposits->isOverdue($deposit))
                                <flux:badge size="sm" color="red">{{ __('deposit::pending.overdue') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
