<section wire:poll.60s class="flex flex-col gap-4">
    <x-section-heading :title="__('dashboard.fleet.heading')" />

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ($counts as $statusValue => $machinesCount)
            @php($machineStatus = \Functional\Fleet\Enums\MachineStatus::from($statusValue))
            <a
                href="{{ route('machines.index', array_filter(['agence' => $agencyId, 'statut' => $statusValue])) }}"
                wire:navigate
                wire:key="fleet-{{ $statusValue }}"
                class="flex flex-col gap-2 rounded-xl border border-zinc-200 p-4 hover:bg-zinc-50 focus-visible:outline-2 dark:border-zinc-700 dark:hover:bg-zinc-700/50"
            >
                <flux:badge size="sm" :color="$machineStatus->color()" class="self-start">{{ $machineStatus->label() }}</flux:badge>
                <span class="text-2xl font-semibold text-zinc-800 dark:text-white">{{ $machinesCount }}</span>
            </a>
        @endforeach
    </div>
</section>
