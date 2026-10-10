@php($fleetTotal = array_sum($counts))

<section wire:poll.60s class="flex flex-col gap-5">
    <div class="flex items-end justify-between gap-4">
        <x-section-heading :title="__('dashboard.fleet.heading')" />
        <p class="text-sm text-zinc-500 dark:text-white/72">{{ trans_choice('dashboard.fleet.total', $fleetTotal) }}</p>
    </div>

    @php($statusDotClasses = ['green' => 'bg-green-400', 'blue' => 'bg-blue-400', 'amber' => 'bg-amber-400', 'red' => 'bg-red-400'])

    <div aria-hidden="true" class="flex h-3 w-full bg-zinc-100 dark:bg-white/16">
        @foreach ($counts as $statusValue => $machinesCount)
            @if ($fleetTotal > 0 && $machinesCount > 0)
                <span class="{{ $statusDotClasses[\Functional\Fleet\Enums\MachineStatus::from($statusValue)->color()] ?? 'bg-zinc-400' }}" style="width: {{ round($machinesCount / $fleetTotal * 100, 2) }}%"></span>
            @endif
        @endforeach
    </div>

    <div class="grid grid-cols-2 border-t-2 border-zinc-950 lg:grid-cols-4 dark:border-white">
        @foreach ($counts as $statusValue => $machinesCount)
            @php($machineStatus = \Functional\Fleet\Enums\MachineStatus::from($statusValue))
            <a
                href="{{ route('machines.index', array_filter(['agence' => $agencyId, 'statut' => $statusValue])) }}"
                wire:navigate
                wire:key="fleet-{{ $statusValue }}"
                @class([
                    'flex flex-col gap-3 border-b border-zinc-100 py-4 pe-4 hover:bg-zinc-50 lg:border-b-0 lg:border-s lg:ps-4 lg:first:border-s-0 lg:first:ps-0 dark:border-white/16 dark:hover:bg-zinc-900',
                    'text-zinc-500 dark:text-white/72' => $machinesCount === 0,
                ])
            >
                <span class="flex items-center gap-2 text-sm font-semibold">
                    <span aria-hidden="true" class="size-2.5 rounded-full {{ $statusDotClasses[$machineStatus->color()] ?? 'bg-zinc-400' }}"></span>
                    {{ $machineStatus->label() }}
                </span>
                <span class="display-md">{{ $machinesCount }}</span>
            </a>
        @endforeach
    </div>
</section>
