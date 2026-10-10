<form wire:submit="refuse" class="flex flex-col gap-5">
    <div>
        <flux:heading size="lg">{{ __('portal::staff.refuse.title') }}</flux:heading>
        <flux:text>{{ __('portal::staff.refuse.summary', ['name' => $reservationRequest->account->name, 'reference' => $reservationRequest->machine->reference]) }}</flux:text>
    </div>

    <flux:textarea wire:model="reason" :label="__('portal::staff.refuse.reason')" :description="__('portal::staff.refuse.reason_hint')" rows="3" required />

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <div class="flex justify-end gap-2">
        <flux:modal.close>
            <flux:button variant="ghost">{{ __('portal::staff.cancel') }}</flux:button>
        </flux:modal.close>
        <flux:button type="submit" variant="danger" data-test="portal-refuse-request">{{ __('portal::staff.refuse.submit') }}</flux:button>
    </div>
</form>
