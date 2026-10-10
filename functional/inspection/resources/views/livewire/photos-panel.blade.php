<section class="flex flex-col gap-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <x-section-heading :title="__('inspection::panel.title')" />

        @if ($openStep !== null)
            <flux:button size="sm" icon="qr-code" wire:click="generate">
                {{ $activeSession === null ? __('inspection::panel.generate', ['step' => $openStep->label()]) : __('inspection::panel.regenerate') }}
            </flux:button>
        @endif
    </div>

    <x-loading-hint />

    <div wire:offline>
        <flux:callout variant="warning" icon="signal-slash" :heading="__('screens.connection_lost')" />
    </div>

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    @if ($openStep === null && $views->isEmpty())
        <flux:text>{{ __('inspection::photos.refusals.no_open_step') }}</flux:text>
    @elseif ($activeSession !== null)
        <div class="flex flex-wrap items-center gap-4">
            @if ($qrCode !== null)
                <div class="rounded-lg bg-white p-2" role="img" aria-label="{{ __('inspection::panel.qr_label', ['step' => $openStep->label()]) }}">{!! $qrCode !!}</div>
            @endif
            <div
                class="flex flex-col gap-2"
                x-data="{ remaining: Math.max(0, {{ $activeSession->expires_at->getTimestamp() }} - Math.floor(Date.now() / 1000)) }"
                x-init="setInterval(() => remaining = Math.max(0, remaining - 1), 1000)"
            >
                <flux:text>{{ __('inspection::panel.scan', ['step' => $openStep->label()]) }}</flux:text>
                <flux:text size="sm">
                    {{ __('inspection::panel.expires_in') }}
                    <span x-text="`${Math.floor(remaining / 60)} min ${String(remaining % 60).padStart(2, '0')} s`"></span>
                </flux:text>
                @if ($qrCode === null)
                    <flux:text size="sm">{{ __('inspection::panel.qr_hidden') }}</flux:text>
                @endif
            </div>
        </div>
    @endif

    @if ($views->isNotEmpty())
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('inspection::panel.view') }}</flux:table.column>
                @foreach (\Functional\Inspection\Enums\InspectionStep::cases() as $step)
                    <flux:table.column>{{ ucfirst($step->label()) }}</flux:table.column>
                @endforeach
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($views as $view)
                    <flux:table.row wire:key="panel-view-{{ $view->id }}">
                        <flux:table.cell variant="strong">{{ $view->label }}</flux:table.cell>
                        @foreach (\Functional\Inspection\Enums\InspectionStep::cases() as $step)
                            @php($stepPhotos = $view->photos->filter(fn ($photo) => $photo->step === $step))
                            <flux:table.cell>
                                @if ($stepPhotos->isEmpty())
                                    <flux:badge color="red" size="sm">{{ __('inspection::phone.view.missing') }}</flux:badge>
                                @else
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($stepPhotos as $photo)
                                            <div wire:key="panel-photo-{{ $photo->id }}" class="relative">
                                                <img src="{{ route('inspection.photo-file', [$photo, 'thumb']) }}" alt="{{ $view->label }}" class="size-16 rounded object-cover" />
                                                @if (! $step->isValidatedFor($reservation))
                                                    <div class="absolute top-1 right-1">
                                                        <flux:button
                                                            size="xs"
                                                            variant="danger"
                                                            icon="trash"
                                                            wire:click="confirmPhotoDeletion({{ $photo->id }})"
                                                            :aria-label="__('inspection::phone.delete')"
                                                        />
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </flux:table.cell>
                        @endforeach
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    @if ($canCompare)
        <div>
            <flux:button size="sm" icon="arrows-right-left" :href="route('inspection.comparison', $reservation)" wire:navigate>
                {{ __('inspection::panel.compare') }}
            </flux:button>
        </div>
    @endif

    <flux:modal name="delete-photo" class="max-w-md">
        <div class="flex flex-col gap-6">
            <div class="flex flex-col gap-2">
                <x-section-heading :title="__('inspection::phone.delete_heading')" />
                <flux:text>{{ __('inspection::phone.delete_confirm') }}</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('screens.cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deletePhoto({{ (int) $photoIdToDelete }})">{{ __('inspection::phone.delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
