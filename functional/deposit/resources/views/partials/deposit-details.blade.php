<dl class="grid gap-1 text-sm sm:grid-cols-[auto_1fr] sm:gap-x-4">
    <dt class="text-zinc-500">{{ __('deposit::section.method') }}</dt>
    <dd>{{ $deposit->payment_method->label() }}{{ $deposit->payment_reference ? ' · '.$deposit->payment_reference : '' }}</dd>
    <dt class="text-zinc-500">{{ __('deposit::section.collected') }}</dt>
    <dd>{{ __('deposit::section.by', ['author' => $deposit->collector->name, 'agency' => $deposit->collectedAgency->name, 'date' => $deposit->collected_at->timezone(config('deposit.timezone'))->format('d/m/Y H:i')]) }}</dd>
</dl>
