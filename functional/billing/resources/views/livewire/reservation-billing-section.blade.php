<section class="space-y-3">
    <flux:heading size="lg">{{ __('billing::periods.section.title') }}</flux:heading>

    @if ($periodTransmissions->isEmpty())
        <flux:text>{{ __('billing::periods.section.empty') }}</flux:text>
    @else
        <flux:heading size="sm">{{ __('billing::periods.section.periods') }}</flux:heading>
        <ul class="space-y-2">
            @foreach ($periodTransmissions as $transmission)
                <li class="flex flex-wrap items-center gap-2">
                    <span>{{ $transmission->billablePeriod->kind->label() }}</span>
                    <span>{{ __('billing::periods.section.period', ['start' => $transmission->billablePeriod->start_date->format('d/m/Y'), 'end' => $transmission->billablePeriod->end_date->format('d/m/Y')]) }}</span>
                    <span>{{ trans_choice('billing::periods.section.days', $transmission->billablePeriod->days, ['days' => $transmission->billablePeriod->days]) }}</span>
                    @include('billing::partials.transmission-status', ['transmission' => $transmission])
                </li>
            @endforeach
        </ul>
    @endif
</section>
