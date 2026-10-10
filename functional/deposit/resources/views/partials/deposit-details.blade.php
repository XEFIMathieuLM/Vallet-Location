<dl class="grid gap-1 text-sm sm:grid-cols-[auto_1fr] sm:gap-x-4">
    <dt class="text-zinc-500">{{ __('deposit::section.method') }}</dt>
    <dd>{{ $deposit->payment_method->label() }}{{ $deposit->payment_reference ? ' · '.$deposit->payment_reference : '' }}</dd>
    <dt class="text-zinc-500">{{ __('deposit::section.collected') }}</dt>
    <dd>{{ __('deposit::section.by', ['author' => $deposit->collector->name, 'agency' => $deposit->collectedAgency->name, 'date' => $deposit->collected_at->timezone(config('deposit.timezone'))->format('d/m/Y H:i')]) }}</dd>
    @if ($deposit->closed_at)
        <dt class="text-zinc-500">{{ __('deposit::section.closing') }}</dt>
        <dd>{{ __('deposit::section.closing_by', ['retained' => $deposit->retained?->format(), 'refunded' => $deposit->refunded?->format(), 'author' => $deposit->closer?->name, 'agency' => $deposit->closedAgency?->name, 'date' => $deposit->closed_at->timezone(config('deposit.timezone'))->format('d/m/Y H:i')]) }}</dd>
    @endif
</dl>
