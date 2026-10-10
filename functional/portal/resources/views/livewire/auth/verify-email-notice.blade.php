<div class="flex flex-col gap-6">
    <x-auth-header :title="__('portal::auth.verify.title')" :description="__('portal::auth.verify.description', ['email' => $email])" />

    @error('resend')
        <flux:callout variant="danger" icon="exclamation-triangle" :heading="$message" />
    @enderror

    <flux:button wire:click="resend" variant="primary" class="w-full" data-test="portal-resend-verification">
        {{ __('portal::auth.verify.resend') }}
    </flux:button>

    <form method="POST" action="{{ route('portal.logout') }}" class="text-center">
        @csrf
        <flux:button type="submit" variant="ghost" size="sm">{{ __('portal::navigation.logout') }}</flux:button>
    </form>
</div>
