@if (app(\Functional\Inspection\Support\DamageActions::class)->isEmpty())
    @can('damages.manage')
        <flux:button size="sm" icon="check" wire:click="resolveDamage({{ $damage->id }})" wire:confirm="{{ __('inspection::damages.resolve_confirm') }}">
            {{ __('inspection::damages.resolve') }}
        </flux:button>
    @endcan
@else
    <div class="flex flex-wrap gap-2">
        @foreach (app(\Functional\Inspection\Support\DamageActions::class)->all() as $damageActionComponent)
            <livewire:dynamic-component :component="$damageActionComponent" :damage="$damage" :key="'damage-action-'.$damage->id.'-'.$damageActionComponent" />
        @endforeach
    </div>
@endif
