<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        <div x-data="{ isOffline: ! navigator.onLine }" x-on:offline.window="isOffline = true" x-on:online.window="isOffline = false" x-show="isOffline" x-cloak class="mb-6" role="alert">
            <flux:callout variant="danger" icon="exclamation-triangle" :heading="__('screens.connection_lost')" />
        </div>

        @can(\Functional\Billing\Enums\BillingPermission::Manage->value)
            <livewire:billing.alert />
        @endcan
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
