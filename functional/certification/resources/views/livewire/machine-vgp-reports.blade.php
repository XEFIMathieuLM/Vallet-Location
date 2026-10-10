<div class="space-y-6">
    <x-page-heading :title="__('certification::reports.screens.machine_title', ['reference' => $machine->reference])">
        <x-slot:actions>
            <flux:button size="sm" :href="route('certification.machines')" wire:navigate>{{ __('certification::reports.screens.back') }}</flux:button>
        </x-slot:actions>
    </x-page-heading>

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <form wire:submit="deposit" class="space-y-4">
        <x-section-heading :title="__('certification::reports.screens.deposit_heading')" level="3" />
        <flux:input type="file" wire:model="reportFile" :label="__('certification::reports.screens.file')" :description="__('certification::reports.screens.file_help', ['formats' => implode(', ', config('certification.accepted_mimes')), 'megabytes' => intdiv(config('certification.max_report_kilobytes'), 1024)])" />
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <flux:input type="date" wire:model="verifiedOn" :label="__('certification::reports.screens.verified_on')" />
            <flux:input type="date" wire:model="dueOn" :label="__('certification::reports.screens.due_on')" />
        </div>
        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">{{ __('certification::reports.screens.deposit') }}</flux:button>
            <x-loading-hint wire:target="deposit,reportFile" />
        </div>
    </form>

    <x-section-heading :title="__('certification::reports.screens.reports_heading')" level="3" />
    @if ($reports->isEmpty())
        <x-empty-state :heading="__('certification::reports.screens.no_report')" :description="__('certification::reports.screens.no_report_description')" />
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('certification::reports.screens.file') }}</flux:table.column>
                <flux:table.column>{{ __('certification::reports.screens.verified_on') }}</flux:table.column>
                <flux:table.column>{{ __('certification::reports.screens.due_on') }}</flux:table.column>
                <flux:table.column>{{ __('certification::reports.screens.deposit_column') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($reports as $report)
                    <flux:table.row :key="$report->id">
                        <flux:table.cell>
                            <flux:link :href="route('certification.reports.file', $report)">{{ $report->original_name }}</flux:link>
                            @if ($loop->first)
                                <flux:badge size="sm" color="green">{{ __('certification::reports.screens.in_force') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $report->verified_on->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $report->due_on->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>{{ __('certification::reports.screens.deposited_by', ['date' => $report->created_at->timezone(config('certification.timezone'))->format('d/m/Y'), 'author' => $report->depositor->getAttribute('name')]) }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
