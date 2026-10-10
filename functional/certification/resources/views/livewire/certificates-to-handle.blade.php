<div class="space-y-6">
    <x-page-heading :title="__('certification::certificates.to_handle.title')" />
    <flux:text>{{ __('certification::certificates.to_handle.intro', ['minutes' => config('certification.alert_after_minutes')]) }}</flux:text>

    @if ($certificates->isEmpty())
        <x-empty-state :heading="__('certification::certificates.to_handle.empty_heading')" :description="__('certification::certificates.to_handle.empty')" />
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('certification::certificates.to_handle.departure') }}</flux:table.column>
                <flux:table.column>{{ __('certification::certificates.to_handle.reservation') }}</flux:table.column>
                <flux:table.column>{{ __('certification::certificates.to_handle.customer') }}</flux:table.column>
                <flux:table.column>{{ __('certification::certificates.to_handle.email') }}</flux:table.column>
                <flux:table.column>{{ __('certification::certificates.to_handle.date') }}</flux:table.column>
                <flux:table.column>{{ __('certification::certificates.to_handle.reason') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($certificates as $certificate)
                    <flux:table.row :key="$certificate->id">
                        <flux:table.cell>{{ $certificate->reservation->start_date->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:link :href="route('reservations.show', $certificate->reservation)" wire:navigate>{{ $certificate->reservation->machine->reference }}</flux:link>
                        </flux:table.cell>
                        <flux:table.cell>{{ $certificate->reservation->customer->name }}</flux:table.cell>
                        <flux:table.cell>{{ $certificate->lastDispatch?->recipient_email ?? $certificate->reservation->customer->email ?? __('certification::certificates.section.no_email') }}</flux:table.cell>
                        <flux:table.cell>{{ $certificate->status_changed_at->timezone(config('certification.timezone'))->format('d/m/Y H:i') }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="space-y-1">
                                <flux:badge size="sm" :color="$certificate->status->color()">{{ $certificate->status->label() }}</flux:badge>
                                @if ($certificate->last_failure_reason)
                                    <flux:text>{{ $certificate->last_failure_reason->label() }}</flux:text>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
