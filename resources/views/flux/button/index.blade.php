@blaze(fold: true, unsafe: ['icon:trailing', 'icon:leading', 'icon:variant'])

@php $iconTrailing ??= $attributes->pluck('icon:trailing'); @endphp
@php $iconLeading ??= $attributes->pluck('icon:leading'); @endphp
@php $iconVariant ??= $attributes->pluck('icon:variant'); @endphp

@props([
    'iconTrailing' => null,
    'variant' => 'outline',
    'iconVariant' => null,
    'iconLeading' => null,
    'type' => 'button',
    'loading' => null,
    'align' => 'center',
    'size' => 'base',
    'square' => null,
    'inset' => null,
    'icon' => null,
    'kbd' => null,
])

@php
$iconLeading = $icon ??= $iconLeading;

// Button should be a square if it has no text contents...
$square ??= $slot->isEmpty() && ! ($iconLeading && $iconTrailing);

// Size-up icons in square/icon-only buttons... (xs buttons just get micro size/style...)
$iconVariant ??= ($size === 'xs')
    ? ($square ? 'micro' : 'micro')
    : ($square ? 'mini' : 'micro');

$iconTrailingVariant ??= $attributes->pluck('icon-trailing:variant', $iconVariant);

// When using the outline icon variant, we need to size it down to match the default icon sizes...
$iconClasses = Flux::classes()
    ->add($iconVariant === 'outline' ? ($square && $size !== 'xs' ? 'size-5' : 'size-4') : '')
    ->add($attributes->pluck('icon:class'))
    ;

$iconTrailingClasses = Flux::classes()
    ->add($iconTrailingVariant === 'outline' ? ($square && $size !== 'xs' ? 'size-5' : 'size-4') : '')
    ->add($attributes->pluck('icon-trailing:class'))
    ;

$isTypeSubmitAndNotDisabledOnRender = $type === 'submit' && ! $attributes->has('disabled');

$isJsMethod = str_starts_with($attributes->whereStartsWith('wire:click')->first() ?? '', '$js.');

$loading ??= $loading ?? ($isTypeSubmitAndNotDisabledOnRender || $attributes->whereStartsWith('wire:click')->isNotEmpty() && ! $isJsMethod);

if ($loading && $type !== 'submit' && ! $isJsMethod) {
    $attributes = $attributes->merge(['wire:loading.attr' => 'data-flux-loading']);

    // We need to add `wire:target` here because without it the loading indicator won't be scoped
    // by method params, causing multiple buttons with the same method but different params to
    // trigger each other's loading indicators...
    if (! $attributes->has('wire:target') && $target = $attributes->whereStartsWith('wire:click')->first()) {
        $attributes = $attributes->merge(['wire:target' => $target], escape: false);
    }
}

$classes = Flux::classes()
    ->add('relative items-center font-bold tracking-[0.01em] justify-center whitespace-nowrap rounded-xs')
    ->add('disabled:opacity-50 disabled:cursor-default disabled:pointer-events-none')
    ->add(match ($align) {
        'start' => 'justify-start',
        'center' => 'justify-center',
        'end' => 'justify-end',
    })
    ->add(match ($size) {
        'base' => 'h-11 sm:h-10 text-sm gap-2' . ' ' . (
            $square
                ? 'w-11 sm:w-10'
                : ($iconLeading && $iconLeading !== '' ? 'ps-3' : 'ps-5') . ' ' . ($iconTrailing && $iconTrailing !== '' ? 'pe-3' : 'pe-5')
        ),
        'sm' => 'h-11 sm:h-8 text-sm gap-2' . ' ' . (
            $square
                ? 'w-11 sm:w-8'
                : ($iconLeading && $iconLeading !== '' ? 'ps-2' : 'ps-3') . ' ' . ($iconTrailing && $iconTrailing !== '' ? 'pe-2' : 'pe-3')
        ),
        'xs' => 'h-6 text-xs gap-1' . ' ' . (
            $square
                ? 'w-6'
                : ($iconLeading && $iconLeading !== '' ? 'ps-1' : 'ps-2') . ' ' . ($iconTrailing && $iconTrailing !== '' ? 'pe-1' : 'pe-2')
        ),
    })
    ->add('inline-flex')
    ->add($inset ? match ($size) {
        'base' => $square
            ? Flux::applyInset($inset, top: '-mt-2.5', right: '-me-2.5', bottom: '-mb-2.5', left: '-ms-2.5')
            : Flux::applyInset($inset, top: '-mt-2.5', right: ($iconTrailing && $iconTrailing !== '' ? '-me-3' : '-me-5'), bottom: '-mb-3', left: ($iconLeading && $iconLeading !== '' ? '-ms-3' : '-ms-5')),
        'sm' => $square
            ? Flux::applyInset($inset, top: '-mt-1.5', right: '-me-1.5', bottom: '-mb-1.5', left: '-ms-1.5')
            : Flux::applyInset($inset, top: '-mt-1.5', right: ($iconTrailing && $iconTrailing !== '' ? '-me-2' : '-me-3'), bottom: '-mb-1.5', left: ($iconLeading && $iconLeading !== '' ? '-ms-2' : '-ms-3')),
        'xs' => $square
            ? Flux::applyInset($inset, top: '-mt-1', right: '-me-1', bottom: '-mb-1', left: '-ms-1')
            : Flux::applyInset($inset, top: '-mt-1', right: ($iconTrailing && $iconTrailing !== '' ? '-me-1' : '-me-2'), bottom: '-mb-1', left: ($iconLeading && $iconLeading !== '' ? '-ms-1' : '-ms-2')),
    } : '')
    ->add(match ($variant) {
        'primary' => 'bg-(--color-accent) hover:bg-(--color-accent-pressed) active:bg-(--color-accent-pressed) text-(--color-accent-foreground)',
        'filled' => 'bg-black text-white hover:bg-zinc-800 dark:bg-white dark:text-black dark:hover:bg-zinc-200',
        'outline' => 'bg-white text-black border border-black hover:bg-zinc-50 dark:bg-black dark:text-white dark:border-white dark:hover:bg-zinc-900',
        'danger' => 'bg-red-600 text-white hover:bg-red-700',
        'ghost' => 'bg-transparent text-black hover:underline underline-offset-4 dark:text-white',
        'subtle' => 'bg-transparent text-zinc-500 hover:text-black dark:text-zinc-300 dark:hover:text-white',
    })
    ->add($loading ? [
        '*:transition-opacity',
        $type === 'submit' ? '[&[disabled]>:not([data-flux-loading-indicator])]:opacity-0' : '[&[data-loading]>:not([data-flux-loading-indicator])]:opacity-0 [&[data-flux-loading]>:not([data-flux-loading-indicator])]:opacity-0',
        $type === 'submit' ? '[&[disabled]>[data-flux-loading-indicator]]:opacity-100' : '[&[data-loading]>[data-flux-loading-indicator]]:opacity-100 [&[data-flux-loading]>[data-flux-loading-indicator]]:opacity-100',
        $type === 'submit' ? '[&[disabled]]:pointer-events-none' : 'data-loading:pointer-events-none data-flux-loading:pointer-events-none',
    ] : [])
    ;

    // Exempt subtle and ghost buttons from receiving border roundness overrides from button.group...
    $attributes = $attributes->merge([
        'data-flux-group-target' => ! in_array($variant, ['subtle', 'ghost']),
    ]);
@endphp

<flux:with-tooltip :$attributes>
    <flux:button-or-link-pure :$type :attributes="$attributes->class($classes)" data-flux-button data-variant="{{ $variant }}">
        <?php if ($loading): ?>
            <div class="absolute inset-0 flex items-center justify-center opacity-0" data-flux-loading-indicator>
                <flux:icon icon="loading" :variant="$iconVariant" :class="$iconClasses" />
            </div>
        <?php endif; ?>

        <?php if (is_string($iconLeading) && $iconLeading !== ''): ?>
            <flux:icon :icon="$iconLeading" :variant="$iconVariant" :class="$iconClasses" />
        <?php elseif ($iconLeading): ?>
            {{ $iconLeading }}
        <?php endif; ?>

        <?php if (($loading || $iconLeading || $iconTrailing) && ! $slot->isEmpty()): ?>
            {{-- If we have a loading indicator, we need to wrap it in a span so it can be a target of *:opacity-0... --}}
            {{-- Also, if we have an icon, we need to wrap it in a span so it can be recognized as a child of the button for :first-child selectors... --}}
            <span>{{ $slot }}</span>
        <?php else: ?>
            {{ $slot }}
        <?php endif; ?>

        <?php if ($kbd): ?>
            <div class="text-xs text-zinc-400 dark:text-zinc-400">{{ $kbd }}</div>
        <?php endif; ?>

        <?php if (is_string($iconTrailing) && $iconTrailing !== ''): ?>
            {{-- Adding the extra margin class inline on the icon component below was causing a double up, so it needs to be added here first... --}}
            <?php $iconClasses->add($square ? '' : '-ms-1'); ?>
            <flux:icon :icon="$iconTrailing" :variant="$iconTrailingVariant" :class="$iconTrailingClasses" />
        <?php elseif ($iconTrailing): ?>
            {{ $iconTrailing }}
        <?php endif; ?>
    </flux:button-or-link-pure>
</flux:with-tooltip>
