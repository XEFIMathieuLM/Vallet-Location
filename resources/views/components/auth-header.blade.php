@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-3">
    <h1 class="display-md text-zinc-950 dark:text-white" data-flux-heading>{{ $title }}</h1>
    <flux:subheading>{{ $description }}</flux:subheading>
</div>
