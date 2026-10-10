<div class="flex flex-col gap-6">
    <x-auth-header :title="__('portal::auth.register.title')" :description="__('portal::auth.register.description')" />

    <form wire:submit="register" class="flex flex-col gap-6">
        <flux:input wire:model="name" :label="__('portal::auth.fields.name')" :description="__('portal::auth.fields.name_hint')" required autofocus autocomplete="organization" />

        <flux:radio.group wire:model="declaredType" :label="__('portal::auth.fields.type')" variant="cards" class="max-sm:flex-col">
            @foreach ($customerTypes as $customerType)
                <flux:radio :value="$customerType->value" :label="$customerType->label()" />
            @endforeach
        </flux:radio.group>

        <flux:input wire:model="email" :label="__('portal::auth.fields.email')" type="email" required autocomplete="email" placeholder="email@exemple.fr" />
        <flux:input wire:model="phone" :label="__('portal::auth.fields.phone')" type="tel" required autocomplete="tel" />
        <flux:input wire:model="password" :label="__('portal::auth.fields.password')" type="password" required autocomplete="new-password" viewable />
        <flux:input wire:model="password_confirmation" :label="__('portal::auth.fields.password_confirmation')" type="password" required autocomplete="new-password" viewable />

        <flux:button type="submit" variant="primary" class="w-full" data-test="portal-register-button">
            {{ __('portal::auth.register.submit') }}
        </flux:button>
    </form>

    <flux:text class="text-center">
        {{ __('portal::auth.register.has_account') }}
        <flux:link :href="route('portal.login')" wire:navigate>{{ __('portal::auth.login.submit') }}</flux:link>
    </flux:text>
</div>
