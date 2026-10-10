<div class="space-y-6">
    <flux:heading size="xl">{{ __('billing::statement.title') }}</flux:heading>

    <div class="flex flex-wrap gap-4">
        <flux:select wire:model.live="agencyId" :label="__('billing::statement.agency')" class="max-w-xs">
            <flux:select.option value="">{{ __('billing::statement.all_agencies') }}</flux:select.option>
            @foreach ($agencies as $agency)
                <flux:select.option :value="$agency->id">{{ $agency->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input type="month" wire:model.live="month" :label="__('billing::statement.month')" class="max-w-xs" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <flux:card>
            <flux:text>{{ __('billing::statement.transmitted_rentals') }}</flux:text>
            <flux:heading size="xl">{{ $statementFigures->transmittedRentalsCount }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text>{{ __('billing::statement.billed_damages') }}</flux:text>
            <flux:heading size="xl">{{ __('billing::statement.amount', ['amount' => $euroAmount->format($statementFigures->billedDamagesTotalCents)]) }}</flux:heading>
        </flux:card>
    </div>

    <section class="space-y-2">
        <flux:heading size="lg">{{ __('billing::statement.waived_damages') }}</flux:heading>
        @forelse ($statementFigures->waivedDamages as $damageSettlement)
            <flux:text>
                {{ __('billing::statement.waived_line', [
                    'machine' => $damageSettlement->damage->reservation->machine->reference,
                    'view' => $damageSettlement->damage->view->label,
                    'reason' => $damageSettlement->waiver_reason,
                    'author' => $damageSettlement->settler->name,
                    'date' => $damageSettlement->settled_at->format('d/m/Y'),
                ]) }}
            </flux:text>
        @empty
            <flux:text>{{ __('billing::statement.none') }}</flux:text>
        @endforelse
    </section>

    <section class="space-y-2">
        <flux:heading size="lg">{{ __('billing::statement.unresolved_damages') }}</flux:heading>
        @forelse ($statementFigures->unresolvedDamages as $damage)
            <div class="flex flex-wrap items-center gap-2">
                <flux:link :href="route('inspection.comparison', $damage->reservation)" wire:navigate>{{ $damage->reservation->machine->reference }}</flux:link>
                <flux:text>{{ $damage->view->label }} : {{ $damage->comment }}</flux:text>
                <flux:text>{{ trans_choice('billing::statement.age', $ageInDays($damage), ['days' => $ageInDays($damage)]) }}</flux:text>
                @if ($ageInDays($damage) > $overdueDays)
                    <flux:badge color="red" size="sm">{{ __('billing::statement.overdue') }}</flux:badge>
                @endif
            </div>
        @empty
            <flux:text>{{ __('billing::statement.none') }}</flux:text>
        @endforelse
    </section>
</div>
