<section wire:poll.60s class="flex flex-col gap-4">
    <x-section-heading :title="__('dashboard.vgp.heading')" />

    <flux:card class="flex flex-col gap-3">
        @forelse ($machines->items as $machine)
            <a
                href="{{ route('certification.machines.show', $machine) }}"
                wire:navigate
                wire:key="vgp-{{ $machine->id }}"
                class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 rounded-lg px-3 py-2 hover:bg-zinc-100 focus-visible:outline-2 dark:hover:bg-zinc-700/50"
            >
                <span class="flex min-w-0 flex-col">
                    <flux:text variant="strong">{{ $machine->reference }} · {{ $machine->category->name }}</flux:text>
                    <flux:badge size="sm" :color="$machine->status->color()" class="self-start">{{ $machine->status->label() }}</flux:badge>
                </span>
                @if ($machine->vgp_due_date === null)
                    <flux:badge size="sm" color="red">{{ __('dashboard.vgp.missing') }}</flux:badge>
                @elseif ($machine->vgp_due_date->lt($today))
                    <flux:badge size="sm" color="red">{{ __('dashboard.vgp.expired', ['date' => $machine->vgp_due_date->format('d/m/Y')]) }}</flux:badge>
                @else
                    <flux:badge size="sm" color="amber">{{ trans_choice('dashboard.vgp.expires', (int) $today->diffInDays($machine->vgp_due_date), ['date' => $machine->vgp_due_date->format('d/m/Y')]) }}</flux:badge>
                @endif
            </a>
        @empty
            <flux:text>{{ __('dashboard.vgp.empty') }}</flux:text>
        @endforelse
        @include('livewire.dashboard.partials.section-more', ['section' => $machines, 'url' => route('certification.machines', array_filter(['agence' => $agencyId])), 'label' => __('dashboard.vgp.see_all')])
    </flux:card>
</section>
