<div class="flex min-w-0 flex-col gap-6">
    <x-page-heading :title="__('booking::reservations.planning.title')" />

    <div class="grid gap-4 md:grid-cols-4">
        <flux:select wire:model.live="categoryId" :label="__('booking::reservations.fields.category')">
            <flux:select.option value="">{{ __('booking::reservations.availability.all_categories') }}</flux:select.option>
            @foreach ($categories as $category)
                <flux:select.option :value="$category->id">{{ $category->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="agencyId" :label="__('booking::reservations.fields.home_agency')">
            <flux:select.option value="">{{ __('booking::reservations.availability.all_agencies') }}</flux:select.option>
            @foreach ($agencies as $agency)
                <flux:select.option :value="$agency->id">{{ $agency->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input type="date" wire:model.live="startDate" :label="__('booking::reservations.fields.start_date')" />
        <flux:input type="date" wire:model.live="endDate" :label="__('booking::reservations.fields.end_date')" />
    </div>

    <div class="flex flex-wrap gap-x-6 gap-y-3 text-sm font-semibold">
        @foreach ($cellKinds as $cellKind)
            <span class="flex items-center gap-2">
                <span class="flex size-5 items-center justify-center border border-zinc-300 text-[10px] font-bold dark:border-white/30 {{ $cellKind->cssClasses() }}" aria-hidden="true">{{ $cellKind->shortLabel() }}</span>
                {{ $cellKind->label() }}
            </span>
        @endforeach
    </div>

    <x-loading-hint />

    @if ($grid === null)
        <flux:callout variant="warning" icon="exclamation-triangle" :heading="__('booking::reservations.availability.invalid_period')" />
    @elseif ($grid->rows === [])
        <x-empty-state :heading="__('booking::reservations.planning.empty')" :description="__('booking::reservations.planning.empty_help')">
            <x-slot:actions>
                <flux:button size="sm" wire:click="clearFilters">{{ __('screens.clear_filters') }}</flux:button>
            </x-slot:actions>
        </x-empty-state>
    @else
        <div class="relative max-w-full min-w-0 overflow-x-auto border-t-2 border-zinc-950 dark:border-white" wire:loading.class="opacity-50">
            <table class="min-w-full border-collapse text-sm">
                <thead>
                    <tr>
                        <th class="eyebrow sticky left-0 bg-white py-3 pe-4 text-left text-zinc-500 dark:bg-black dark:text-white/72">{{ __('booking::reservations.fields.reference') }}</th>
                        @foreach ($grid->days as $day)
                            <th class="px-1 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-white/72">{{ $day->format('d/m') }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grid->rows as $row)
                        <tr wire:key="planning-{{ $row->machine->id }}" class="border-t border-zinc-100 dark:border-white/16">
                            <th class="sticky left-0 bg-white py-3 pe-4 text-left font-bold whitespace-nowrap dark:bg-black">
                                <span class="flex flex-wrap items-center gap-2">
                                    {{ $row->machine->reference }}
                                    @include('fleet::partials.machine-badges', ['badges' => $machineBadges[$row->machine->id] ?? []])
                                </span>
                                <span class="block text-xs font-normal text-zinc-500 dark:text-white/72">{{ $row->machine->category->name }} · {{ $row->machine->agency->name }}</span>
                            </th>
                            @foreach ($row->cells as $date => $cell)
                                @php($cellLabel = __('booking::reservations.planning.cell_label', ['date' => \Carbon\CarbonImmutable::parse($date)->format('d/m/Y'), 'state' => $cell->label()]))
                                <td class="h-12 min-w-11 border-l border-zinc-100 p-1 dark:border-white/16">
                                    @if ($cell->reservation !== null)
                                        <a href="{{ route('reservations.show', $cell->reservation) }}" wire:navigate title="{{ $cellLabel }}" aria-label="{{ $cellLabel }}"
                                            class="flex size-full items-center justify-center text-xs font-bold hover:outline-2 hover:outline-offset-1 hover:outline-current {{ $cell->kind->cssClasses() }}">{{ $cell->kind->shortLabel() }}</a>
                                    @else
                                        <span title="{{ $cellLabel }}" class="flex size-full items-center justify-center text-xs font-bold {{ $cell->kind->cssClasses() }}">
                                            <span aria-hidden="true">{{ $cell->kind->shortLabel() }}</span>
                                            <span class="sr-only">{{ $cellLabel }}</span>
                                        </span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $machines->links() }}
    @endif
</div>
