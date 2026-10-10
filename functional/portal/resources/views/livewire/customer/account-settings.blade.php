<div class="flex max-w-xl flex-col gap-8">
    <x-page-heading :title="__('portal::auth.account.title')" />

    <flux:card class="flex flex-col gap-2">
        <flux:text>{{ __('portal::auth.account.email', ['email' => $account->email]) }}</flux:text>
        <flux:text>{{ __('portal::auth.account.type', ['type' => $account->declared_type->label()]) }}</flux:text>
        <flux:text class="text-sm">{{ __('portal::auth.account.record_hint') }}</flux:text>
    </flux:card>

    <form wire:submit="updateProfile" class="flex flex-col gap-4">
        <x-section-heading level="2" :title="__('portal::auth.account.profile')" />
        <flux:input wire:model="name" :label="__('portal::auth.fields.name')" required />
        <flux:input wire:model="phone" :label="__('portal::auth.fields.phone')" type="tel" required />
        <div><flux:button type="submit" variant="primary">{{ __('portal::auth.account.save') }}</flux:button></div>
    </form>

    <form wire:submit="updatePassword" class="flex flex-col gap-4">
        <x-section-heading level="2" :title="__('portal::auth.account.password')" />
        <flux:input wire:model="currentPassword" :label="__('portal::auth.fields.current_password')" type="password" required autocomplete="current-password" viewable />
        <flux:input wire:model="password" :label="__('portal::auth.fields.new_password')" type="password" required autocomplete="new-password" viewable />
        <flux:input wire:model="password_confirmation" :label="__('portal::auth.fields.password_confirmation')" type="password" required autocomplete="new-password" viewable />
        <div><flux:button type="submit" variant="primary">{{ __('portal::auth.account.save_password') }}</flux:button></div>
    </form>
</div>
