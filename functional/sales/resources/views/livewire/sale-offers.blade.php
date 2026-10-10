<section class="flex flex-col gap-4">
    <x-section-heading level="3" :title="__('sales::sales.offers.title')" />

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    @if ($this->offers->isEmpty())
        <x-empty-state :heading="__('sales::sales.offers.empty_heading')" :description="__('sales::sales.offers.empty')" />
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('sales::sales.fields.buyer') }}</flux:table.column>
                <flux:table.column align="end">{{ __('sales::sales.offers.amount') }}</flux:table.column>
                <flux:table.column>{{ __('sales::sales.offers.offered_on') }}</flux:table.column>
                <flux:table.column>{{ __('sales::sales.offers.status') }}</flux:table.column>
                <flux:table.column />
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->offers as $offer)
                    <flux:table.row :key="$offer->id">
                        <flux:table.cell>{{ $offer->customer->name }}</flux:table.cell>
                        <flux:table.cell align="end">{{ $offer->amount->format() }} €</flux:table.cell>
                        <flux:table.cell>{{ $offer->offered_on->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$offer->status->color()">{{ $offer->status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell align="end">
                            @if ($offer->status === \Functional\Sales\Enums\OfferStatus::Pending && $sale->state()->acceptsOffers())
                                <flux:button size="xs" variant="primary" wire:click="confirmAcceptance({{ $offer->id }})">{{ __('sales::sales.offers.accept') }}</flux:button>
                                <flux:button size="xs" wire:click="reject({{ $offer->id }})">{{ __('sales::sales.offers.reject') }}</flux:button>
                                <flux:button size="xs" variant="ghost" wire:click="withdraw({{ $offer->id }})">{{ __('sales::sales.offers.withdraw') }}</flux:button>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    @if ($sale->state()->acceptsOffers())
        <form wire:submit="record" class="flex flex-col gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading>{{ __('sales::sales.offers.new') }}</flux:heading>
            <flux:switch wire:model.live="isNewCustomer" :label="__('sales::sales.offers.new_customer')" />
            @if ($isNewCustomer)
                <div class="grid gap-4 md:grid-cols-3">
                    <flux:input wire:model="newCustomerName" :label="__('sales::sales.offers.customer_name')" />
                    <flux:input wire:model="newCustomerPhone" :label="__('sales::sales.offers.customer_phone')" />
                    <flux:input type="email" wire:model="newCustomerEmail" :label="__('sales::sales.offers.customer_email')" />
                </div>
                <flux:radio.group wire:model="newCustomerType" :label="__('booking::customers.fields.type_label')" variant="segmented">
                    @foreach (\Functional\Booking\Enums\CustomerType::cases() as $customerType)
                        <flux:radio :value="$customerType->value" :label="$customerType->label()" />
                    @endforeach
                </flux:radio.group>
            @else
                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input wire:model.live.debounce.300ms="customerSearch" icon="magnifying-glass" :label="__('sales::sales.offers.customer_search')" />
                    <flux:select wire:model="customerId" :label="__('sales::sales.fields.buyer')">
                        <flux:select.option value="">{{ __('sales::sales.offers.choose_customer') }}</flux:select.option>
                        @foreach ($this->customers as $customer)
                            <flux:select.option :value="$customer->id">{{ $customer->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            @endif
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="amount" :label="__('sales::sales.offers.amount_input')" />
                <flux:input type="date" wire:model="offeredOn" :label="__('sales::sales.offers.offered_on')" />
            </div>
            <div>
                <flux:button type="submit" variant="primary">{{ __('sales::sales.offers.record') }}</flux:button>
            </div>
        </form>
    @endif

    <flux:modal name="accept-offer" class="md:w-lg">
        <form wire:submit="accept" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ __('sales::sales.offers.accept_title') }}</flux:heading>
            <flux:text>{{ __('sales::sales.offers.accept_help') }}</flux:text>
            <flux:input type="date" wire:model="plannedHandoverDate" :label="__('sales::sales.offers.planned_handover_date')" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('sales::sales.form.back') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('sales::sales.offers.accept') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
