<div class="flex max-w-2xl flex-col gap-6">
    <x-page-heading :title="__('booking::reservations.form.title')" />

    <flux:card class="flex flex-col gap-1">
        <x-section-heading level="4" :title="$this->machine->reference" />
        <flux:text>{{ $this->machine->category->name }} · {{ __('booking::reservations.fields.home_agency') }} : {{ $this->machine->agency->name }}</flux:text>
    </flux:card>

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <form wire:submit="save" class="flex flex-col gap-4">
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
            <flux:radio.group wire:model="newCustomerType" :label="__('booking::customers.fields.type_label')" variant="segmented">
                @foreach ($customerTypes as $customerType)
                    <flux:radio :value="$customerType->value" :label="$customerType->label()" />
                @endforeach
            </flux:radio.group>
        @else
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model.live.debounce.300ms="customerSearch" icon="magnifying-glass" :label="__('booking::reservations.form.customer_search')" />
                <flux:select wire:model="customerId" :label="__('booking::reservations.fields.customer')">
                    <flux:select.option value="">{{ __('booking::reservations.form.choose_customer') }}</flux:select.option>
                    @foreach ($this->customers as $customer)
                        <flux:select.option :value="$customer->id">{{ collect($this->customerBadges[$customer->id] ?? [])->pluck('label')->prepend($customer->name.' · '.($customer->type?->label() ?? __('booking::customers.type_missing')))->implode(' — ') }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            @foreach ($customerId !== null ? $this->customerBadges[$customerId] ?? [] : [] as $badge)
                <div class="flex items-center gap-2">
                    <flux:badge size="sm" :color="$badge->color" :href="$badge->url">{{ $badge->label }}</flux:badge>
                    @if ($badge->description !== null)
                        <flux:text size="sm">{{ $badge->description }}</flux:text>
                    @endif
                </div>
            @endforeach
        @endif

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('booking::reservations.form.confirm') }}</flux:button>
            <flux:button :href="route('availability.index')" wire:navigate variant="ghost">{{ __('booking::reservations.form.back') }}</flux:button>
        </div>
    </form>
</div>
