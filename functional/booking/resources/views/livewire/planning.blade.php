<div class="flex flex-col gap-6">
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

    <div class="flex flex-wrap gap-4 text-sm">
        @foreach ($cellKinds as $cellKind)
            <span class="flex items-center gap-2">
                <span class="inline-block size-3 rounded-sm border border-zinc-300 dark:border-zinc-600 {{ $cellKind->cssClasses() }}"></span>
                {{ $cellKind->label() }}
            </span>
        @endforeach
    </div>

    @if ($grid === null)
        <flux:callout variant="warning" icon="exclamation-triangle" :heading="__('booking::reservations.availability.invalid_period')" />
    @else
        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full border-collapse text-sm">
                <thead>
                    <tr>
                        <th class="sticky left-0 bg-white px-4 py-2 text-left dark:bg-zinc-800">{{ __('booking::reservations.fields.reference') }}</th>
                        @foreach ($grid->days as $day)
                            <th class="px-2 py-2 text-center font-normal text-zinc-600 dark:text-zinc-300">{{ $day->format('d/m') }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grid->rows as $row)
                        <tr wire:key="planning-{{ $row->machine->id }}" class="border-t border-zinc-200 dark:border-zinc-700">
                            <th class="sticky left-0 bg-white px-4 py-2 text-left font-medium whitespace-nowrap dark:bg-zinc-800">
                                {{ $row->machine->reference }}
                                <span class="block text-xs font-normal text-zinc-600 dark:text-zinc-300">{{ $row->machine->category->name }} · {{ $row->machine->agency->name }}</span>
                            </th>
                            @foreach ($row->cells as $date => $cell)
                                <td class="h-8 min-w-8 border-l border-zinc-100 p-0.5 dark:border-zinc-700" title="{{ $cell->label() }}">
                                    @if ($cell->reservation !== null)
                                        <a href="{{ route('reservations.show', $cell->reservation) }}" wire:navigate class="block size-full rounded-sm {{ $cell->kind->cssClasses() }}"></a>
                                    @else
                                        <span class="block size-full rounded-sm {{ $cell->kind->cssClasses() }}"></span>
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
