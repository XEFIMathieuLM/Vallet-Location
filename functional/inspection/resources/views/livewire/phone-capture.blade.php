<div class="flex flex-col gap-4">
    <header class="flex flex-col gap-1">
        <flux:heading size="lg">{{ $reservation->machine->reference }}</flux:heading>
        <flux:text>{{ $reservation->customer->name }}</flux:text>
        <flux:badge color="blue" class="self-start">{{ __('inspection::phone.step', ['step' => $step->label()]) }}</flux:badge>
    </header>

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <ul class="flex flex-col gap-3">
        @foreach ($views as $view)
            <li wire:key="view-{{ $view->id }}" class="flex flex-col gap-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                <div class="flex items-center justify-between gap-2">
                    <flux:heading>{{ $view->label }}</flux:heading>
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
                                <img src="{{ $thumbnailUrls[$photo->id] }}" alt="{{ $view->label }}" class="size-24 rounded object-cover" />
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

                <div x-data="photoResize({{ $view->id }})" class="flex flex-col gap-1">
                    <label class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg bg-zinc-800 px-4 py-3 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
                        <span x-show="state !== 'uploading'">{{ __('inspection::phone.take_photo') }}</span>
                        <span x-show="state === 'uploading'" x-cloak>{{ __('inspection::phone.uploading') }} <span x-text="progress"></span>%</span>
                        <input type="file" accept="image/*" capture="environment" class="sr-only" x-on:change="send($event.target.files[0]); $event.target.value = ''" />
                    </label>
                    <div x-show="state === 'failed'" x-cloak class="flex items-center justify-between gap-2">
                        <flux:text class="text-red-600 dark:text-red-400">{{ __('inspection::phone.not_sent') }}</flux:text>
                        <flux:button size="sm" x-on:click="retry()">{{ __('inspection::phone.retry') }}</flux:button>
                    </div>
                    @error("uploads.{$view->id}")
                        <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </div>
            </li>
        @endforeach
    </ul>
</div>
