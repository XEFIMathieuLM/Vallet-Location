<div class="flex flex-col gap-6">
    <x-auth-header :title="__('portal::auth.login.title')" :description="__('portal::auth.login.description')" />

    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="login" class="flex flex-col gap-6">
        <flux:input wire:model="email" :label="__('portal::auth.fields.email')" type="email" required autofocus autocomplete="email" placeholder="email@exemple.fr" />

        <div class="relative">
            <flux:input wire:model="password" :label="__('portal::auth.fields.password')" type="password" required autocomplete="current-password" viewable />
            <flux:link class="absolute top-0 text-sm end-0" :href="route('portal.password.request')" wire:navigate>
                {{ __('portal::auth.login.forgot') }}
            </flux:link>
        </div>

        <flux:checkbox wire:model="is_remembered" :label="__('portal::auth.login.remember')" />

        <flux:button type="submit" variant="primary" class="w-full" data-test="portal-login-button">
            {{ __('portal::auth.login.submit') }}
        </flux:button>
    </form>

    <flux:text class="text-center">
        {{ __('portal::auth.login.no_account') }}
        <flux:link :href="route('portal.register')" wire:navigate>{{ __('portal::auth.register.submit') }}</flux:link>
    </flux:text>
</div>
