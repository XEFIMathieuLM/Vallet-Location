@if ($transmission->failure_reason === \Functional\Billing\Enums\TransmissionFailureReason::CustomerUnknown)
    <flux:text size="sm">{{ __('billing::transmissions.reasons.customer_unknown') }}</flux:text>
@elseif ($transmission->failure_reason === \Functional\Billing\Enums\TransmissionFailureReason::Rejected)
    <flux:text size="sm">{{ __('billing::transmissions.reasons.rejected') }}</flux:text>
@elseif ($transmission->next_attempt_at !== null)
    <flux:text size="sm">{{ __('billing::transmissions.reasons.unreachable', ['date' => $transmission->next_attempt_at->format('d/m/Y H:i')]) }}</flux:text>
@else
    <flux:text size="sm">{{ __('billing::transmissions.reasons.waiting') }}</flux:text>
@endif
@if ($transmission->last_error && $transmission->failure_reason !== \Functional\Billing\Enums\TransmissionFailureReason::CustomerUnknown)
    <details class="text-sm">
        <summary class="cursor-pointer">{{ __('billing::transmissions.reasons.technical_detail') }}</summary>
        <flux:text size="sm" class="mt-2">{{ $transmission->last_error }}</flux:text>
    </details>
@endif
