<div class="space-y-6">
    <x-page-heading :title="__('deposit::rates.category_title', ['category' => $category->name])" />
    <flux:text>{{ __('deposit::rates.effective', ['amount' => $effectiveAmount->format()]) }} · {{ $hasOwnRate ? __('deposit::rates.own') : __('deposit::rates.default') }}</flux:text>

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <form wire:submit="save" class="flex flex-wrap items-end gap-2">
        <flux:input wire:model="amount" inputmode="decimal" :label="__('deposit::rates.amount')" />
        <flux:button type="submit" variant="primary">{{ __('deposit::rates.save') }}</flux:button>
        @if ($hasOwnRate)
            <flux:button variant="ghost" wire:click="remove">{{ __('deposit::rates.remove') }}</flux:button>
        @endif
        <x-loading-hint wire:target="save,remove" />
    </form>

    <flux:button variant="ghost" icon="arrow-left" :href="route('deposit.rates.index')" wire:navigate>{{ __('deposit::rates.back') }}</flux:button>
</div>
