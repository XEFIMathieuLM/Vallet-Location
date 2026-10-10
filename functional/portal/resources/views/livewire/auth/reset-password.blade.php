<div class="flex flex-col gap-6">
    <x-auth-header :title="__('portal::auth.reset.title')" :description="__('portal::auth.reset.description')" />

    <form wire:submit="resetPassword" class="flex flex-col gap-6">
        <flux:input wire:model="email" :label="__('portal::auth.fields.email')" type="email" required autocomplete="email" />
        <flux:input wire:model="password" :label="__('portal::auth.fields.password')" type="password" required autocomplete="new-password" viewable />
        <flux:input wire:model="password_confirmation" :label="__('portal::auth.fields.password_confirmation')" type="password" required autocomplete="new-password" viewable />
        <flux:button type="submit" variant="primary" class="w-full">{{ __('portal::auth.reset.submit') }}</flux:button>
    </form>
</div>
