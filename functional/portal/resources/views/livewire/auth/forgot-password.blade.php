<div class="flex flex-col gap-6">
    <x-auth-header :title="__('portal::auth.forgot.title')" :description="__('portal::auth.forgot.description')" />

    @if ($is_sent)
        <flux:callout variant="success" icon="envelope" :heading="__('portal::auth.forgot.sent')" />
    @endif

    <form wire:submit="sendResetLink" class="flex flex-col gap-6">
        <flux:input wire:model="email" :label="__('portal::auth.fields.email')" type="email" required autofocus autocomplete="email" />
        <flux:button type="submit" variant="primary" class="w-full">{{ __('portal::auth.forgot.submit') }}</flux:button>
    </form>

    <flux:text class="text-center">
        <flux:link :href="route('portal.login')" wire:navigate>{{ __('portal::auth.forgot.back') }}</flux:link>
    </flux:text>
</div>
