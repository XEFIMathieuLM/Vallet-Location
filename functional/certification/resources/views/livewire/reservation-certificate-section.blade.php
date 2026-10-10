<section class="space-y-4">
    @if ($isConcerned)
        @error('refusal')
            <flux:callout variant="danger" icon="x-circle" :heading="$message" />
        @enderror

        <x-section-heading :title="__('certification::certificates.section.title')" />

        @if ($certificate === null)
            <flux:text>{{ __('certification::certificates.section.opening') }}</flux:text>
        @else
            <div class="flex flex-wrap items-center gap-2">
                <flux:badge size="sm" :color="$certificate->status->color()">{{ $certificate->status->label() }}</flux:badge>
                @if ($certificate->status->isDelivered())
                    <flux:text>{{ __('certification::certificates.section.delivered_on', ['date' => $certificate->delivered_at?->timezone(config('certification.timezone'))->format('d/m/Y H:i')]) }}</flux:text>
                @endif
                @if ($certificate->last_failure_reason)
                    <flux:text>{{ $certificate->last_failure_reason->label() }}</flux:text>
                @endif
            </div>

            <dl class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-zinc-500">{{ __('certification::certificates.section.recipient') }}</dt>
                    <dd>{{ $certificate->lastDispatch?->recipient_email ?? $reservation->customer->email ?? __('certification::certificates.section.no_email') }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-500">{{ __('certification::certificates.section.last_dispatch') }}</dt>
                    <dd>{{ $certificate->lastDispatch?->attempted_at->timezone(config('certification.timezone'))->format('d/m/Y H:i') ?? __('certification::certificates.section.never') }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-500">{{ __('certification::certificates.section.attempts') }}</dt>
                    <dd>{{ $certificate->attempts }}</dd>
                </div>
            </dl>
        @endif
    @endif
</section>
