<div class="flex flex-col gap-8">
    <nav aria-label="{{ __('Settings') }}" class="flex flex-wrap gap-2">
        @foreach (['profile.edit' => __('Profile'), 'security.edit' => __('Security'), 'appearance.edit' => __('Appearance')] as $routeName => $label)
            <a
                href="{{ route($routeName) }}"
                wire:navigate
                @if (request()->routeIs($routeName)) aria-current="page" @endif
                @class([
                    'flex h-11 items-center rounded-full border px-4 text-sm font-semibold sm:h-9',
                    'border-zinc-950 bg-zinc-950 text-white dark:border-white dark:bg-white dark:text-black' => request()->routeIs($routeName),
                    'border-zinc-950 hover:bg-zinc-50 dark:border-white dark:hover:bg-zinc-900' => ! request()->routeIs($routeName),
                ])
            >{{ $label }}</a>
        @endforeach
    </nav>

    <div class="flex flex-col gap-2 border-t-2 border-zinc-950 pt-6 dark:border-white">
        <x-section-heading :title="$heading ?? ''" />
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
