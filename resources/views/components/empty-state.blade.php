@props(['heading', 'description' => null])

<div {{ $attributes->class('flex flex-col items-start gap-4 rounded-lg border border-dashed border-zinc-300 p-6 dark:border-zinc-600') }}>
    <div class="flex flex-col gap-2">
        <x-section-heading level="4" :title="$heading" />
        @if ($description !== null)
            <flux:text>{{ $description }}</flux:text>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap gap-2">{{ $actions }}</div>
    @endisset
</div>
