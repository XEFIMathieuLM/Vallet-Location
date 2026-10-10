<div class="flex flex-col gap-6">
    <x-page-heading :title="__('portal::navigation.requests')">
        <x-slot:actions>
            <flux:button size="sm" icon="magnifying-glass" :href="route('portal.search')" wire:navigate>{{ __('portal::requests.new') }}</flux:button>
        </x-slot:actions>
    </x-page-heading>

    @if (session('request-sent'))
        <flux:callout variant="success" icon="check-circle" :heading="session('request-sent')" />
    @endif

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    @if ($reservationRequests->isEmpty())
        <x-empty-state :heading="__('portal::requests.empty')" :description="__('portal::requests.empty_help')" />
    @else
        <div class="flex flex-col gap-4">
            @foreach ($reservationRequests as $reservationRequest)
                <flux:card wire:key="request-{{ $reservationRequest->id }}" class="flex flex-col gap-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <flux:heading size="lg">{{ $reservationRequest->machine->reference }} · {{ $reservationRequest->machine->category->name }}</flux:heading>
                        <flux:badge size="sm" :color="$reservationRequest->status->color()">{{ $reservationRequest->status->label() }}</flux:badge>
                    </div>
                    <flux:text>
                        {{ __('portal::requests.period', ['start' => $reservationRequest->start_date->format('d/m/Y'), 'end' => $reservationRequest->end_date->format('d/m/Y'), 'agency' => $reservationRequest->machine->agency->name]) }}
                    </flux:text>
                    <flux:text size="sm">{{ __('portal::requests.sent_on', ['date' => $reservationRequest->created_at->timezone('Europe/Paris')->format('d/m/Y H:i')]) }}</flux:text>
                    @if ($reservationRequest->indicative_daily_price_cents !== null)
                        <flux:text size="sm">{{ __('portal::requests.price_shown', ['amount' => $priceFormatter->amount($reservationRequest->indicative_daily_price_cents)]) }}</flux:text>
                    @endif
                    @if ($reservationRequest->refusal_reason !== null)
                        <flux:callout variant="danger" icon="x-circle" :heading="__('portal::requests.refusal_reason', ['reason' => $reservationRequest->refusal_reason])" />
                    @endif
                    @if ($reservationRequest->reservation !== null)
                        <flux:text>
                            {{ __('portal::requests.reserved_machine', ['reference' => $reservationRequest->reservation->machine->reference]) }}
                            <flux:link :href="route('portal.reservations')" wire:navigate>{{ __('portal::requests.see_reservations') }}</flux:link>
                        </flux:text>
                    @endif
                    @if ($reservationRequest->state()->isOpen())
                        <div>
                            <flux:modal.trigger :name="'cancel-request-'.$reservationRequest->id">
                                <flux:button size="sm" variant="ghost" icon="x-mark">{{ __('portal::requests.cancel') }}</flux:button>
                            </flux:modal.trigger>
                        </div>
                        <flux:modal :name="'cancel-request-'.$reservationRequest->id" class="md:w-96">
                            <div class="flex flex-col gap-4">
                                <flux:heading size="lg">{{ __('portal::requests.cancel_title') }}</flux:heading>
                                <flux:text>{{ __('portal::requests.cancel_confirm') }}</flux:text>
                                <div class="flex justify-end gap-2">
                                    <flux:modal.close><flux:button variant="ghost">{{ __('portal::requests.keep') }}</flux:button></flux:modal.close>
                                    <flux:button variant="danger" wire:click="cancel({{ $reservationRequest->id }})">{{ __('portal::requests.cancel') }}</flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    @endif
                </flux:card>
            @endforeach
        </div>
        <flux:pagination :paginator="$reservationRequests" />
    @endif
</div>
