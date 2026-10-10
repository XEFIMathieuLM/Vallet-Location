<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        @can(\Functional\Billing\Enums\BillingPermission::Manage->value)
            <livewire:billing.alert />
        @endcan
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
