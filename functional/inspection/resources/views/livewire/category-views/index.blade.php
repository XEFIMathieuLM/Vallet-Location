<section class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('inspection::views.index.title') }}</flux:heading>
        <flux:text>{{ __('inspection::views.index.subtitle') }}</flux:text>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('inspection::views.index.category') }}</flux:table.column>
            <flux:table.column>{{ __('inspection::views.index.views_count') }}</flux:table.column>
            <flux:table.column>{{ __('inspection::views.index.list') }}</flux:table.column>
            <flux:table.column />
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($categories as $category)
                <flux:table.row wire:key="category-{{ $category->id }}">
                    <flux:table.cell variant="strong">{{ $category->name }}</flux:table.cell>
                    <flux:table.cell>{{ $customViewCounts[$category->id] ?? $defaultViewCount }}</flux:table.cell>
                    <flux:table.cell>
                        @if (isset($customViewCounts[$category->id]))
                            <flux:badge color="blue" size="sm">{{ __('inspection::views.index.custom') }}</flux:badge>
                        @else
                            <flux:badge size="sm">{{ __('inspection::views.index.default') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:button size="sm" :href="route('inspection.category-views.edit', $category)" wire:navigate>
                            {{ __('inspection::views.index.edit') }}
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
