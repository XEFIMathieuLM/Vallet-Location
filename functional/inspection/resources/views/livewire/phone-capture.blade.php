<div class="flex flex-col gap-6">
    <header class="flex flex-col gap-2">
        <x-page-heading :title="$reservation->machine->reference" />
        <flux:text>{{ $reservation->customer->name }}</flux:text>
        <flux:badge color="blue" size="sm" class="self-start">{{ __('inspection::phone.step', ['step' => $step->label()]) }}</flux:badge>
    </header>

    <div wire:offline>
        <flux:callout variant="warning" icon="signal-slash" :heading="__('inspection::phone.offline')" />
    </div>

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <ul class="flex flex-col gap-4">
        @foreach ($views as $view)
            <li wire:key="view-{{ $view->id }}" class="flex flex-col gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center justify-between gap-2">
                    <x-section-heading :title="$view->label" />
                    @if ($view->photos->isEmpty())
                        <flux:badge color="red" size="sm">{{ __('inspection::phone.view.missing') }}</flux:badge>
                    @else
                        <flux:badge color="green" size="sm">{{ __('inspection::phone.view.received') }}</flux:badge>
                    @endif
                </div>

                @if ($view->photos->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach ($view->photos as $photo)
                            <div wire:key="photo-{{ $photo->id }}" class="relative">
                                <img src="{{ $thumbnailUrls[$photo->id] }}" alt="{{ __('inspection::phone.photo_of', ['view' => $view->label]) }}" class="size-24 rounded object-cover" />
                                <div class="absolute top-1 right-1">
                                    <flux:button
                                        size="xs"
                                        variant="danger"
                                        icon="trash"
                                        wire:click="deletePhoto({{ $photo->id }})"
                                        wire:confirm="{{ __('inspection::phone.delete_confirm') }}"
                                        :aria-label="__('inspection::phone.delete')"
                                    />
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div x-data="photoResize({{ $view->id }})" class="flex flex-col gap-2">
                    <input x-ref="file" type="file" accept="image/*" capture="environment" class="hidden" tabindex="-1" aria-hidden="true" x-on:change="send($event.target.files[0]); $event.target.value = ''" />
                    <flux:button type="button" icon="camera" class="h-12 self-start" x-on:click="$refs.file.click()" x-bind:disabled="state === 'uploading'">
                        <span x-show="state !== 'uploading'">{{ __('inspection::phone.take_photo') }}</span>
                        <span x-show="state === 'uploading'" x-cloak>{{ __('inspection::phone.uploading') }} <span x-text="progress"></span>%</span>
                    </flux:button>
                    <div x-show="state === 'failed'" x-cloak class="flex items-center justify-between gap-2" role="alert">
                        <flux:text class="text-red-600 dark:text-red-400">{{ __('inspection::phone.not_sent') }}</flux:text>
                        <flux:button type="button" class="h-12" x-on:click="retry()">{{ __('inspection::phone.retry') }}</flux:button>
                    </div>
                    @error("uploads.{$view->id}")
                        <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </div>
            </li>
        @endforeach
    </ul>
</div>
