<flux:badge size="sm" :color="$transmission->status->color()">{{ $transmission->status->label() }}</flux:badge>
@if ($transmission->status === \Functional\Billing\Enums\TransmissionStatus::Sent)
    <flux:text size="sm">{{ __('billing::periods.section.sent_on', ['date' => $transmission->sent_at?->format('d/m/Y H:i')]) }}</flux:text>
@elseif ($transmission->status === \Functional\Billing\Enums\TransmissionStatus::Exported)
    <flux:text size="sm">{{ __('billing::periods.section.exported_on', ['date' => $transmission->updated_at?->format('d/m/Y H:i')]) }}</flux:text>
@elseif ($transmission->status === \Functional\Billing\Enums\TransmissionStatus::Failed)
    <flux:text size="sm">{{ __('billing::periods.section.failed_reason', ['reason' => $transmission->last_error]) }}</flux:text>
@else
    <flux:text size="sm">{{ __('billing::periods.section.pending_since', ['date' => $transmission->created_at->format('d/m/Y H:i')]) }}</flux:text>
@endif
