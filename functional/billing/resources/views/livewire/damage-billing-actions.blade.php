<div x-data="{ mode: null }" class="space-y-2">
    @can('billing.manage')
        <div class="flex flex-wrap gap-2" x-show="mode === null">
            <flux:button size="sm" icon="currency-euro" x-on:click="mode = 'bill'">{{ __('billing::damages.bill') }}</flux:button>
            <flux:button size="sm" variant="ghost" x-on:click="mode = 'waive'">{{ __('billing::damages.waive') }}</flux:button>
        </div>

        <form wire:submit="bill" x-show="mode === 'bill'" x-cloak class="flex flex-wrap items-end gap-2">
            <flux:input size="sm" wire:model="amount" :label="__('billing::damages.amount')" inputmode="decimal" />
            <flux:input size="sm" wire:model="label" :label="__('billing::damages.label')" />
            <flux:button size="sm" type="submit" variant="primary">{{ __('billing::damages.confirm_bill') }}</flux:button>
            <flux:button size="sm" variant="ghost" x-on:click="mode = null">{{ __('billing::damages.cancel') }}</flux:button>
        </form>

        <form wire:submit="waive" x-show="mode === 'waive'" x-cloak class="flex flex-wrap items-end gap-2">
            <flux:input size="sm" wire:model="waiverReason" :label="__('billing::damages.waiver_reason')" />
            <flux:button size="sm" type="submit" variant="primary">{{ __('billing::damages.confirm_waive') }}</flux:button>
            <flux:button size="sm" variant="ghost" x-on:click="mode = null">{{ __('billing::damages.cancel') }}</flux:button>
        </form>

        @error('refusal')
            <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text>
        @enderror
    @endcan
</div>
