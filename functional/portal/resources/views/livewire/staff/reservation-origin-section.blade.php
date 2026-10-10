<div>
    @if ($reservationRequest !== null)
        <flux:card class="space-y-2">
            <x-section-heading level="3" :title="__('portal::staff.origin.heading')" />
            <flux:text>{{ __('portal::staff.origin.sent_on', ['date' => $reservationRequest->created_at->timezone('Europe/Paris')->format('d/m/Y')]) }}</flux:text>
            <flux:text>{{ __('portal::staff.origin.account', ['name' => $reservationRequest->account->name, 'email' => $reservationRequest->account->email]) }}</flux:text>
            @if ($reservationRequest->comment !== null)
                <flux:text>{{ __('portal::staff.origin.comment', ['comment' => $reservationRequest->comment]) }}</flux:text>
            @endif
            @if ($reservationRequest->machine_id !== $reservation->machine_id)
                <flux:text>{{ __('portal::staff.origin.requested_machine', ['reference' => $reservationRequest->machine->reference]) }}</flux:text>
            @endif
        </flux:card>
    @endif
</div>
