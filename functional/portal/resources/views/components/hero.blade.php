@props(['title', 'eyebrow' => null])

<section {{ $attributes->class('bg-black text-white') }} data-hero>
    <div class="mx-auto flex max-w-[90rem] flex-col gap-8 px-4 py-12 sm:px-10 sm:py-16">
        <div class="flex flex-col gap-4">
            <p class="eyebrow text-white/72">{{ $eyebrow ?? __('portal::navigation.hero_eyebrow') }}</p>
            <h1 class="display-xl" data-flux-heading>{{ $title }}</h1>
        </div>

        @if ($slot->isNotEmpty())
            <div class="dark flex flex-col gap-6">{{ $slot }}</div>
        @endif
    </div>
</section>
