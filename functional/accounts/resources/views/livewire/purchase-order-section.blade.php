<div>
    @if ($status !== \Functional\Accounts\Enums\PurchaseOrderSectionStatus::Hidden)
        <flux:card class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <x-section-heading level="3" :title="__('accounts::purchase_orders.section.title')" />
                <flux:badge size="sm" :color="$status->color()">{{ $status->label() }}</flux:badge>
            </div>

            @if ($isKeyAccount)
                <flux:text>{{ __('accounts::purchase_orders.section.key_account') }}</flux:text>
            @endif

            @error('refusal')
                <flux:callout variant="danger" icon="x-circle" :heading="$message" />
            @enderror

            @if ($purchaseOrder !== null)
                <div>
                    <div class="font-medium">{{ $purchaseOrder->number }}</div>
                    <flux:text size="sm">{{ __('accounts::purchase_orders.section.entered_by', ['author' => $purchaseOrder->author->getAttribute('name'), 'agency' => $purchaseOrder->agency->name, 'date' => $purchaseOrder->entered_at->format('d/m/Y H:i')]) }}</flux:text>
                </div>
            @endif

            @if ($status->canBeEdited())
                @can(\Functional\Accounts\Access\AccountsPermission::ManagePurchaseOrders->value)
                    <form wire:submit="save" class="flex items-end gap-2">
                        <flux:input wire:model="number" :label="__('accounts::purchase_orders.section.number')" maxlength="{{ config('accounts.purchase_order_max_length') }}" />
                        <flux:button type="submit" size="sm">{{ $purchaseOrder === null ? __('accounts::purchase_orders.section.enter') : __('accounts::purchase_orders.section.correct') }}</flux:button>
                    </form>
                @endcan
            @endif
        </flux:card>
    @endif
</div>
