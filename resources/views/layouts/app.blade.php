<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        @can('billing.manage')
            <livewire:billing.alert />
        @endcan
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
