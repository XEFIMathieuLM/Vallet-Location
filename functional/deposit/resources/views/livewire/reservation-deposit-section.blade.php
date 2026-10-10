@php
    use Functional\Booking\Enums\ReservationStatus;
    use Functional\Deposit\Access\DepositPermission;
    use Functional\Deposit\Enums\DepositSituationKind;
    use Functional\Deposit\Enums\DepositStatus;
@endphp

<section class="space-y-4">
    <x-section-heading :title="__('deposit::section.title')" />

    <div class="flex flex-wrap items-center gap-2">
        @if ($situation->deposit)
            <flux:badge size="sm">{{ $situation->deposit->status->label() }}</flux:badge>
            <span>{{ __('deposit::section.amount', ['amount' => $situation->deposit->amount->format()]) }}</span>
        @else
            <flux:badge size="sm">{{ $situation->kind->label() }}</flux:badge>
            @if ($situation->expectedAmount)
                <span>{{ __('deposit::section.amount', ['amount' => $situation->expectedAmount->format()]) }}</span>
            @endif
        @endif
    </div>

    @if ($situation->deposit)
        @include('deposit::partials.deposit-details', ['deposit' => $situation->deposit])
        @if ($situation->deposit->status === DepositStatus::BlockedByDamage)
            @include('deposit::partials.damages-to-settle', ['damages' => $damagesToSettle])
        @endif
    @endif

    @can(DepositPermission::ManageDeposits->value)
        @if ($reservation->status === ReservationStatus::Confirmed)
            <livewire:deposit.qualify-customer-form :reservation="$reservation" :key="'qualify-customer-'.$reservation->id.'-'.($reservation->customer->type?->value ?? 'none')" />
        @endif
        @if ($situation->kind === DepositSituationKind::ToCollect)
            <livewire:deposit.collect-deposit-form :reservation="$reservation" :key="'collect-deposit-'.$reservation->id" />
        @endif
        @if ($situation->deposit && ! $situation->deposit->status->isFinal())
            <livewire:deposit.correct-payment-form :reservation="$reservation" :key="'correct-payment-'.$reservation->id" />
            <livewire:deposit.close-deposit-actions :reservation="$reservation" :key="'close-deposit-'.$reservation->id.'-'.$situation->deposit->status->value" />
        @endif
    @endcan
</section>
