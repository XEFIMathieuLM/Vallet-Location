<div class="space-y-6">
    <x-page-heading :title="__('portal::staff.requests.title')" />
    <flux:text>{{ __('portal::staff.requests.intro') }}</flux:text>

    <div class="max-w-xs">
        <flux:select wire:model.live="agencyId" :label="__('portal::staff.requests.agency')">
            <flux:select.option value="">{{ __('portal::staff.requests.all_agencies') }}</flux:select.option>
            @foreach ($agencies as $agency)
                <flux:select.option :value="$agency->id">{{ $agency->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <x-loading-hint />

    @if ($reservationRequests->isEmpty())
        <x-empty-state :heading="__('portal::staff.requests.empty')" />
    @else
        <flux:table :paginate="$reservationRequests" wire:loading.class="opacity-50">
            <flux:table.columns>
                <flux:table.column>{{ __('portal::staff.requests.dates') }}</flux:table.column>
                <flux:table.column>{{ __('portal::staff.requests.customer') }}</flux:table.column>
                <flux:table.column>{{ __('portal::staff.requests.machine') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($reservationRequests as $reservationRequest)
                    <flux:table.row :key="$reservationRequest->id">
                        <flux:table.cell class="whitespace-nowrap">
                            {{ $reservationRequest->start_date->format('d/m/Y') }} → {{ $reservationRequest->end_date->format('d/m/Y') }}
                            <flux:text size="sm">{{ __('portal::staff.requests.sent_on', ['date' => $reservationRequest->created_at->timezone('Europe/Paris')->format('d/m/Y H:i')]) }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium">{{ $reservationRequest->account->name }}</span>
                                <flux:badge size="sm">{{ $reservationRequest->account->declared_type->label() }}</flux:badge>
                            </div>
                            <flux:text size="sm">{{ $reservationRequest->account->email }} · {{ $reservationRequest->account->phone }}</flux:text>
                            @if ($reservationRequest->account->customer !== null)
                                <flux:text size="sm">{{ __('portal::staff.requests.attached_to', ['name' => $reservationRequest->account->customer->name]) }}</flux:text>
                            @else
                                <flux:badge size="sm" color="amber">{{ __('portal::staff.requests.unattached') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="max-w-xs whitespace-normal">
                            <span class="font-medium">{{ $reservationRequest->machine->reference }}</span>
                            <flux:text size="sm">{{ $reservationRequest->machine->category->name }} · {{ $reservationRequest->machine->agency->name }}</flux:text>
                            @if ($reservationRequest->comment !== null)
                                <flux:text size="sm" class="whitespace-normal">« {{ $reservationRequest->comment }} »</flux:text>
                            @endif
                            <flux:text size="sm">
                                {{ $reservationRequest->indicative_daily_price_cents !== null
                                    ? __('portal::staff.requests.price_shown', ['amount' => $priceFormatter->amount($reservationRequest->indicative_daily_price_cents)])
                                    : __('portal::staff.requests.no_price_shown') }}
                            </flux:text>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex flex-col items-end gap-2">
                                <flux:button size="sm" variant="primary" icon="check" wire:click="startConfirming({{ $reservationRequest->id }})">{{ __('portal::staff.requests.confirm') }}</flux:button>
                                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="startRefusing({{ $reservationRequest->id }})">{{ __('portal::staff.requests.refuse') }}</flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    <flux:modal name="confirm-request" class="md:w-[40rem]">
        @if ($confirmingRequestId !== null)
            <livewire:portal.confirm-request-modal :reservation-request-id="$confirmingRequestId" :key="'confirm-'.$confirmingRequestId" />
        @endif
    </flux:modal>

    <flux:modal name="refuse-request" class="md:w-[32rem]">
        @if ($refusingRequestId !== null)
            <livewire:portal.refuse-request-modal :reservation-request-id="$refusingRequestId" :key="'refuse-'.$refusingRequestId" />
        @endif
    </flux:modal>
</div>
