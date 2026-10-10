<div class="flex flex-col gap-6">
    <x-page-heading :title="__('users.title')" />

    @if (session('user-saved'))
        <flux:callout variant="success" icon="check-circle" :heading="session('user-saved')" />
    @endif

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <flux:card>
        <form wire:submit="create" class="grid items-end gap-4 md:grid-cols-4">
            <flux:input wire:model="name" :label="__('users.fields.name')" />
            <flux:input type="email" wire:model="email" :label="__('users.fields.email')" />
            <flux:select wire:model="agencyId" :label="__('users.fields.agency')">
                <flux:select.option value="">{{ __('users.choose_agency') }}</flux:select.option>
                @foreach ($agencies as $agency)
                    <flux:select.option :value="$agency->id">{{ $agency->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:button type="submit" variant="primary" icon="user-plus">{{ __('users.create') }}</flux:button>
        </form>
        <flux:text size="sm" class="mt-4">{{ __('users.create_help') }}</flux:text>
    </flux:card>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('users.fields.name') }}</flux:table.column>
            <flux:table.column>{{ __('users.fields.email') }}</flux:table.column>
            <flux:table.column>{{ __('users.fields.agency') }}</flux:table.column>
            <flux:table.column>{{ __('users.fields.status') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->users as $user)
                <flux:table.row wire:key="user-{{ $user->id }}">
                    <flux:table.cell variant="strong">{{ $user->name }}</flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:select size="sm" wire:change="changeAgency({{ $user->id }}, $event.target.value)" :aria-label="__('users.agency_of', ['name' => $user->name])">
                            @foreach ($agencies as $agency)
                                <flux:select.option :value="$agency->id" :selected="$agency->id === $user->agency_id">{{ $agency->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$user->isDeactivated() ? 'zinc' : 'green'">
                            {{ $user->isDeactivated() ? __('users.deactivated') : __('users.active') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        @if ($user->isDeactivated())
                            <flux:button size="xs" variant="ghost" wire:click="reactivate({{ $user->id }})">{{ __('users.reactivate') }}</flux:button>
                        @else
                            <flux:button size="xs" variant="ghost" wire:click="confirmDeactivation({{ $user->id }})">{{ __('users.deactivate') }}</flux:button>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="deactivate-user" class="max-w-md">
        <div class="flex flex-col gap-6">
            <div class="flex flex-col gap-2">
                <x-section-heading :title="__('users.deactivate_heading')" />
                <flux:text>{{ __('users.deactivate_confirmation', ['name' => $this->userToDeactivate?->name]) }}</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('users.keep_active') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deactivate({{ $userToDeactivateId ?? 0 }})">{{ __('users.confirm_deactivation') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
