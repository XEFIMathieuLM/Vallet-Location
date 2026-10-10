@php
    use Functional\Deposit\Access\DepositPermission;
    use Functional\Deposit\Enums\DepositSituationKind;
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
    @endif

    @can(DepositPermission::ManageDeposits->value)
        @if ($situation->kind === DepositSituationKind::ToCollect)
            <livewire:deposit.collect-deposit-form :reservation="$reservation" :key="'collect-deposit-'.$reservation->id" />
        @endif
    @endcan
</section>
