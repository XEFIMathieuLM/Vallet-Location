@if (app(\Functional\Inspection\Extensions\DamageActions::class)->isEmpty())
    @can(\Functional\Inspection\Access\InspectionPermission::ManageDamages->value)
        <flux:modal.trigger name="resolve-damage-{{ $damage->id }}">
            <flux:button size="xs" icon="check">{{ __('inspection::damages.resolve') }}</flux:button>
        </flux:modal.trigger>

        <flux:modal name="resolve-damage-{{ $damage->id }}" class="max-w-md">
            <div class="flex flex-col gap-6">
                <div class="flex flex-col gap-2">
                    <x-section-heading :title="__('inspection::damages.resolve')" />
                    <flux:text>{{ __('inspection::damages.resolve_confirm') }}</flux:text>
                </div>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('screens.cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" wire:click="resolveDamage({{ $damage->id }})">{{ __('inspection::damages.resolve') }}</flux:button>
                </div>
            </div>
        </flux:modal>
    @endcan
@else
    <div class="flex flex-wrap gap-2">
        @foreach (app(\Functional\Inspection\Extensions\DamageActions::class)->all() as $damageActionComponent)
            <livewire:dynamic-component :component="$damageActionComponent" :damage="$damage" :key="'damage-action-'.$damage->id.'-'.$damageActionComponent" />
        @endforeach
    </div>
@endif
