@php
    use Functional\Deposit\Enums\DepositStatus;
@endphp

<div class="space-y-3">
    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    @if ($deposit->status === DepositStatus::ToRefund)
        <form wire:submit="refund" class="flex flex-col gap-3">
            @if ($isAfterReturn)
                <flux:checkbox wire:model="isNoDamageConfirmed" :label="__('deposit::section.no_damage_confirmation')" />
            @endif
            <div class="flex items-center gap-2">
                <flux:button type="submit" variant="primary" icon="arrow-uturn-left">{{ __('deposit::section.refund', ['amount' => $deposit->amount->format()]) }}</flux:button>
                <x-loading-hint wire:target="refund" />
            </div>
        </form>
    @elseif ($deposit->status === DepositStatus::ToSettle)
        <dl class="grid gap-1 text-sm sm:grid-cols-[auto_1fr] sm:gap-x-4">
            <dt class="text-zinc-500">{{ __('deposit::section.retained') }}</dt>
            <dd>{{ __('deposit::section.amount', ['amount' => $retention->retained->format()]) }}</dd>
            <dt class="text-zinc-500">{{ __('deposit::section.to_give_back') }}</dt>
            <dd>{{ __('deposit::section.amount', ['amount' => $retention->refunded->format()]) }}</dd>
        </dl>
        <flux:text size="sm">{{ __('deposit::section.retention_help') }}</flux:text>
        <div class="flex items-center gap-2">
            <flux:button variant="primary" wire:click="settle">{{ __('deposit::section.settle') }}</flux:button>
            <x-loading-hint wire:target="settle" />
        </div>
    @endif
</div>
