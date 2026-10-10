<div class="space-y-6">
    <x-page-heading :title="__('certification::reports.screens.machines_title')" />

    <div class="flex flex-wrap items-end gap-4">
        <flux:select wire:model.live="agencyId" :label="__('certification::reports.screens.agency')" class="max-w-xs">
            <flux:select.option value="">{{ __('certification::reports.screens.all_agencies') }}</flux:select.option>
            @foreach ($agencies as $agency)
                <flux:select.option :value="$agency->id">{{ $agency->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:checkbox wire:model.live="isWithoutReportOnly" :label="__('certification::reports.screens.without_report_only')" />
        <x-loading-hint />
    </div>

    @if ($machines->isEmpty())
        <x-empty-state :heading="__('certification::reports.screens.machines_empty_heading')" :description="__('certification::reports.screens.machines_empty')" />
    @else
        <flux:table :paginate="$machines">
            <flux:table.columns>
                <flux:table.column>{{ __('certification::reports.screens.reference') }}</flux:table.column>
                <flux:table.column>{{ __('certification::reports.screens.category') }}</flux:table.column>
                <flux:table.column>{{ __('certification::reports.screens.agency') }}</flux:table.column>
                <flux:table.column>{{ __('certification::reports.screens.verified_on') }}</flux:table.column>
                <flux:table.column>{{ __('certification::reports.screens.due_on') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($machines as $machine)
                    @php($report = $reportsInForce->get($machine->id))
                    <flux:table.row :key="$machine->id">
                        <flux:table.cell>
                            <flux:link :href="route('certification.machines.show', $machine)" wire:navigate>{{ $machine->reference }}</flux:link>
                        </flux:table.cell>
                        <flux:table.cell>{{ $machine->category->name }}</flux:table.cell>
                        <flux:table.cell>{{ $machine->agency->name }}</flux:table.cell>
                        @if ($report)
                            <flux:table.cell>{{ $report->verified_on->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell>{{ $report->due_on->format('d/m/Y') }}</flux:table.cell>
                        @else
                            <flux:table.cell colspan="2">
                                <flux:badge size="sm" color="amber">{{ __('certification::reports.screens.no_report') }}</flux:badge>
                            </flux:table.cell>
                        @endif
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
