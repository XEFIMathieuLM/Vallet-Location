<div class="flex max-w-2xl flex-col gap-6">
    <flux:heading size="xl" level="1">{{ __('booking::reservations.form.title') }}</flux:heading>

    <flux:card class="flex flex-col gap-1">
        <flux:heading>{{ $this->machine->reference }}</flux:heading>
        <flux:text>{{ $this->machine->category->name }} · {{ __('booking::reservations.fields.home_agency') }} : {{ $this->machine->agency->name }}</flux:text>
    </flux:card>

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <form wire:submit="save" class="flex flex-col gap-6">
        <div class="grid gap-4 md:grid-cols-2">
            <flux:input type="date" wire:model="startDate" :label="__('booking::reservations.fields.start_date')" />
            <flux:input type="date" wire:model="endDate" :label="__('booking::reservations.fields.end_date')" />
        </div>

        <flux:switch wire:model.live="isNewCustomer" :label="__('booking::reservations.form.new_customer')" />

        @if ($isNewCustomer)
            <div class="grid gap-4 md:grid-cols-3">
                <flux:input wire:model="newCustomerName" :label="__('booking::reservations.fields.customer_name')" />
                <flux:input wire:model="newCustomerPhone" :label="__('booking::reservations.fields.customer_phone')" />
                <flux:input type="email" wire:model="newCustomerEmail" :label="__('booking::reservations.fields.customer_email')" />
            </div>
            <flux:text size="sm">{{ __('booking::reservations.form.contact_required') }}</flux:text>
        @else
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model.live.debounce.300ms="customerSearch" icon="magnifying-glass" :label="__('booking::reservations.form.customer_search')" />
                <flux:select wire:model="customerId" :label="__('booking::reservations.fields.customer')">
                    <flux:select.option value="">{{ __('booking::reservations.form.choose_customer') }}</flux:select.option>
                    @foreach ($this->customers as $customer)
                        <flux:select.option :value="$customer->id">{{ $customer->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        @endif

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('booking::reservations.form.confirm') }}</flux:button>
            <flux:button :href="route('availability.index')" wire:navigate variant="ghost">{{ __('booking::reservations.form.back') }}</flux:button>
        </div>
    </form>
</div>
