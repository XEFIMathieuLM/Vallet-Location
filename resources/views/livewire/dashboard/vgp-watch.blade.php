<section wire:poll.60s class="flex flex-col gap-5">
    <x-section-heading :title="__('dashboard.vgp.heading')" />

    <div class="flex flex-col border-t-2 border-zinc-950 dark:border-white">
        @forelse ($machines->items as $machine)
            <a
                href="{{ route('certification.machines.show', $machine) }}"
                wire:navigate
                wire:key="vgp-{{ $machine->id }}"
                class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-b border-zinc-100 py-4 hover:bg-zinc-50 dark:border-white/16 dark:hover:bg-zinc-900"
            >
                <span class="flex min-w-0 items-center gap-3">
                    <x-fleet::pictogram :category="$machine->category->name" class="h-8 w-12 shrink-0" />
                    <span class="flex min-w-0 flex-col items-start gap-1">
                        <span class="font-bold">{{ $machine->reference }} · {{ $machine->category->name }}</span>
                        <flux:badge size="sm" :color="$machine->status->color()">{{ $machine->status->label() }}</flux:badge>
                    </span>
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
            <flux:text class="py-4">{{ __('dashboard.vgp.empty') }}</flux:text>
        @endforelse
    </div>
    @include('livewire.dashboard.partials.section-more', ['section' => $machines, 'url' => route('certification.machines', array_filter(['agence' => $agencyId])), 'label' => __('dashboard.vgp.see_all')])
</section>
