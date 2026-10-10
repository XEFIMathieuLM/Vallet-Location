<form wire:submit="confirm" class="flex flex-col gap-5">
    <div>
        <flux:heading size="lg">{{ __('portal::staff.confirm.title') }}</flux:heading>
        <flux:text>{{ __('portal::staff.confirm.summary', ['name' => $reservationRequest->account->name, 'start' => $reservationRequest->start_date->format('d/m/Y'), 'end' => $reservationRequest->end_date->format('d/m/Y')]) }}</flux:text>
    </div>

    @if ($machines->isEmpty())
        <flux:callout variant="warning" icon="exclamation-triangle" :heading="__('portal::staff.confirm.no_machine')" />
    @endif

    <flux:select wire:model="machineId" :label="__('portal::staff.confirm.machine')" :description="__('portal::staff.confirm.machine_hint')">
        @if (! $machines->contains('id', $reservationRequest->machine_id))
            <flux:select.option :value="$reservationRequest->machine_id">{{ __('portal::staff.confirm.requested_unavailable', ['reference' => $reservationRequest->machine->reference]) }}</flux:select.option>
        @endif
        @foreach ($machines as $machine)
            <flux:select.option :value="$machine->id">
                {{ $machine->reference }} · {{ $machine->agency->name }}{{ $machine->id === $reservationRequest->machine_id ? ' — '.__('portal::staff.confirm.requested') : '' }}
            </flux:select.option>
        @endforeach
    </flux:select>

    @if ($reservationRequest->account->customer !== null)
        <flux:callout icon="user" :heading="__('portal::staff.confirm.attached', ['name' => $reservationRequest->account->customer->name])" />
    @else
        <flux:radio.group wire:model.live="customerChoice" :label="__('portal::staff.confirm.customer_record')">
            <flux:radio value="create" :label="__('portal::staff.confirm.create', ['name' => $reservationRequest->account->name])"
                :description="__('portal::staff.confirm.create_hint', ['type' => $reservationRequest->account->declared_type->label(), 'email' => $reservationRequest->account->email, 'phone' => $reservationRequest->account->phone])" />
            <flux:radio value="existing" :label="__('portal::staff.confirm.existing')" :description="__('portal::staff.confirm.existing_hint')" />
        </flux:radio.group>

        @if ($customerChoice === 'existing')
            @if ($suggestedCustomers->isNotEmpty())
                <flux:radio.group wire:model="existingCustomerId" :label="__('portal::staff.confirm.suggested')">
                    @foreach ($suggestedCustomers as $customer)
                        <flux:radio :value="$customer->id" :label="$customer->name" :description="collect([$customer->email, $customer->phone])->filter()->implode(' · ')" />
                    @endforeach
                </flux:radio.group>
            @else
                <flux:text>{{ __('portal::staff.confirm.no_suggestion') }}</flux:text>
            @endif

            <flux:input wire:model.live.debounce.300ms="customerSearch" icon="magnifying-glass" :label="__('portal::staff.confirm.search')" />
            @if ($searchedCustomers->isNotEmpty())
                <flux:radio.group wire:model="existingCustomerId">
                    @foreach ($searchedCustomers as $customer)
                        <flux:radio :value="$customer->id" :label="$customer->name" :description="collect([$customer->email, $customer->phone])->filter()->implode(' · ')" />
                    @endforeach
                </flux:radio.group>
            @endif
            @error('existingCustomerId')
                <flux:text class="text-red-600">{{ __('portal::staff.confirm.choose_record') }}</flux:text>
            @enderror
        @endif
    @endif

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <div class="flex justify-end gap-2">
        <flux:modal.close>
            <flux:button variant="ghost">{{ __('portal::staff.cancel') }}</flux:button>
        </flux:modal.close>
        <flux:button type="submit" variant="primary" data-test="portal-confirm-request">{{ __('portal::staff.confirm.submit') }}</flux:button>
    </div>
</form>
