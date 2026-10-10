<section class="flex max-w-2xl flex-col gap-6">
    <div class="flex flex-col gap-2">
        <flux:link :href="route('inspection.category-views.index')" wire:navigate>{{ __('inspection::views.edit.back') }}</flux:link>
        <x-page-heading :title="__('inspection::views.edit.title', ['category' => $category->name])" />
        @if ($isCustomized)
            <flux:badge color="blue" size="sm" class="self-start">{{ __('inspection::views.index.custom') }}</flux:badge>
        @else
            <flux:badge size="sm" class="self-start">{{ __('inspection::views.index.default') }}</flux:badge>
        @endif
    </div>

    <x-loading-hint />

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <ol class="flex flex-col divide-y divide-zinc-200 rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
        @foreach ($labels as $offset => $label)
            @php($position = $offset + 1)
            <li wire:key="view-{{ $position }}-{{ $label }}" class="flex items-center gap-2 p-4">
                @if ($editedPosition === $position)
                    <form wire:submit="rename" class="flex flex-1 items-center gap-2">
                        <flux:input wire:model="editedLabel" class="flex-1" :aria-label="__('inspection::views.edit.label')" />
                        <flux:button type="submit" size="xs">{{ __('inspection::views.edit.save') }}</flux:button>
                        <flux:button size="xs" wire:click="$set('editedPosition', null)">{{ __('inspection::views.edit.cancel') }}</flux:button>
                    </form>
                @else
                    <flux:text class="flex-1">{{ $position }}. {{ $label }}</flux:text>
                    <flux:button size="xs" variant="ghost" icon="arrow-up" wire:click="move({{ $position }}, -1)" :disabled="$loop->first" :aria-label="__('inspection::views.edit.move_up')" />
                    <flux:button size="xs" variant="ghost" icon="arrow-down" wire:click="move({{ $position }}, 1)" :disabled="$loop->last" :aria-label="__('inspection::views.edit.move_down')" />
                    <flux:button size="xs" variant="ghost" icon="pencil" wire:click="edit({{ $position }}, @js($label))" :aria-label="__('inspection::views.edit.rename')" />
                    <flux:button size="xs" variant="ghost" icon="trash" wire:click="remove({{ $position }})" :aria-label="__('inspection::views.edit.remove')" />
                @endif
            </li>
        @endforeach
    </ol>
    @error('editedLabel')
        <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
    @enderror

    <form wire:submit="add" class="flex items-end gap-2">
        <flux:input wire:model="newLabel" :label="__('inspection::views.edit.new_view')" class="flex-1" />
        <flux:button type="submit" variant="primary">{{ __('inspection::views.edit.add') }}</flux:button>
    </form>

    @if ($isCustomized)
        <div>
            <flux:modal.trigger name="reset-views">
                <flux:button variant="subtle">{{ __('inspection::views.edit.reset') }}</flux:button>
            </flux:modal.trigger>

            <flux:modal name="reset-views" class="max-w-md">
                <div class="flex flex-col gap-6">
                    <div class="flex flex-col gap-2">
                        <x-section-heading :title="__('inspection::views.edit.reset')" />
                        <flux:text>{{ __('inspection::views.edit.reset_confirm') }}</flux:text>
                    </div>
                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">{{ __('screens.cancel') }}</flux:button>
                        </flux:modal.close>
                        <flux:button variant="danger" wire:click="resetToDefault">{{ __('inspection::views.edit.reset') }}</flux:button>
                    </div>
                </div>
            </flux:modal>
        </div>
    @endif
</section>
