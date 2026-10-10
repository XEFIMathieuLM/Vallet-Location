<div class="space-y-6">
    <x-page-heading :title="__('accounts::key_accounts.screen.title')" />
    <flux:text>{{ __('accounts::key_accounts.screen.intro') }}</flux:text>

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    @if ($this->keyAccounts->isEmpty())
        <x-empty-state :heading="__('accounts::key_accounts.screen.empty_heading')" :description="__('accounts::key_accounts.screen.empty')" />
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('accounts::key_accounts.screen.customer') }}</flux:table.column>
                <flux:table.column>{{ __('accounts::key_accounts.screen.billing_ref') }}</flux:table.column>
                <flux:table.column>{{ __('accounts::key_accounts.screen.designated') }}</flux:table.column>
                <flux:table.column />
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->keyAccounts as $keyAccount)
                    <flux:table.row :key="$keyAccount->id">
                        <flux:table.cell>{{ $keyAccount->customer->name }}</flux:table.cell>
                        <flux:table.cell>{{ $this->billingRefs[$keyAccount->customer_id] ?? '' }}</flux:table.cell>
                        <flux:table.cell>{{ __('accounts::key_accounts.screen.designated_by', ['author' => $keyAccount->designator->getAttribute('name'), 'date' => $keyAccount->designated_at->format('d/m/Y')]) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:modal.trigger :name="'revoke-key-account-'.$keyAccount->customer_id">
                                <flux:button size="xs" variant="ghost">{{ __('accounts::key_accounts.screen.revoke') }}</flux:button>
                            </flux:modal.trigger>
                            <flux:modal :name="'revoke-key-account-'.$keyAccount->customer_id" class="max-w-md space-y-4">
                                <flux:heading size="lg">{{ __('accounts::key_accounts.screen.revoke_heading', ['customer' => $keyAccount->customer->name]) }}</flux:heading>
                                <flux:text>{{ __('accounts::key_accounts.screen.revoke_help') }}</flux:text>
                                <div class="flex justify-end gap-2">
                                    <flux:modal.close>
                                        <flux:button variant="ghost">{{ __('accounts::key_accounts.screen.cancel') }}</flux:button>
                                    </flux:modal.close>
                                    <flux:button variant="danger" wire:click="revoke({{ $keyAccount->customer_id }})">{{ __('accounts::key_accounts.screen.revoke') }}</flux:button>
                                </div>
                            </flux:modal>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    <flux:card class="space-y-4">
        <x-section-heading level="3" :title="__('accounts::key_accounts.screen.designate_heading')" />
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :label="__('accounts::key_accounts.screen.search')" />
        <x-loading-hint wire:target="search" />
        @if (trim($search) !== '' && $this->candidates->isEmpty())
            <x-empty-state :heading="__('accounts::key_accounts.screen.no_candidate')" :description="__('accounts::key_accounts.screen.no_candidate_help')" />
        @endif
        @foreach ($this->candidates as $candidate)
            <div class="flex items-center justify-between gap-4" wire:key="candidate-{{ $candidate->id }}">
                <div>
                    <div>{{ $candidate->name }}</div>
                    <flux:text size="sm">{{ $this->billingRefs[$candidate->id] ?? __('accounts::key_accounts.screen.no_billing_ref') }}</flux:text>
                </div>
                <flux:button size="sm" wire:click="designate({{ $candidate->id }})">{{ __('accounts::key_accounts.screen.designate') }}</flux:button>
            </div>
        @endforeach
    </flux:card>
</div>
