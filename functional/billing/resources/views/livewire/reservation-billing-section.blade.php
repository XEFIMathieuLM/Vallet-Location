<section class="space-y-4">
    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <flux:heading size="lg">{{ __('billing::periods.section.title') }}</flux:heading>

    @if ($periodTransmissions->isEmpty() && $damageSettlements->isEmpty())
        <flux:text>{{ __('billing::periods.section.empty') }}</flux:text>
    @endif

    @if ($periodTransmissions->isNotEmpty())
        <flux:heading size="sm">{{ __('billing::periods.section.periods') }}</flux:heading>
        <ul class="space-y-2">
            @foreach ($periodTransmissions as $transmission)
                <li class="flex flex-wrap items-center gap-2">
                    <span>{{ $transmission->billablePeriod->kind->label() }}</span>
                    <span>{{ __('billing::periods.section.period', ['start' => $transmission->billablePeriod->start_date->format('d/m/Y'), 'end' => $transmission->billablePeriod->end_date->format('d/m/Y')]) }}</span>
                    <span>{{ trans_choice('billing::periods.section.days', $transmission->billablePeriod->days, ['days' => $transmission->billablePeriod->days]) }}</span>
                    @include('billing::partials.transmission-status', ['transmission' => $transmission, 'canRetry' => true])
                </li>
            @endforeach
        </ul>
    @endif

    @if ($damageSettlements->isNotEmpty())
        <flux:heading size="sm">{{ __('billing::damages.section.title') }}</flux:heading>
        <ul class="space-y-2">
            @foreach ($damageSettlements as $damageSettlement)
                <li class="flex flex-wrap items-center gap-2">
                    <span>{{ __('billing::damages.section.damage', ['view' => $damageSettlement->damage->view->label, 'comment' => $damageSettlement->damage->comment]) }}</span>
                    <flux:badge size="sm">{{ $damageSettlement->outcome->label() }}</flux:badge>
                    @if ($damageSettlement->transmission)
                        <span>{{ __('billing::damages.section.billed', ['label' => $damageSettlement->label, 'amount' => $damageSettlement->amount?->format()]) }}</span>
                        @include('billing::partials.transmission-status', ['transmission' => $damageSettlement->transmission, 'canRetry' => true])
                    @else
                        <span>{{ __('billing::damages.section.waived', ['reason' => $damageSettlement->waiver_reason, 'author' => $damageSettlement->settler->name, 'date' => $damageSettlement->settled_at->format('d/m/Y')]) }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</section>
