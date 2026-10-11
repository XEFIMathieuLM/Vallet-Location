@props(['category'])

@include(\Functional\Fleet\Enums\MachineFamily::fromCategoryName($category)->pictogramView(), [
    'attributes' => $attributes->merge(['aria-hidden' => 'true', 'focusable' => 'false']),
])
