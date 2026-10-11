<?php

namespace Tests\Feature\Design;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ButtonVariantsTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function variants(): array
    {
        return [
            'primary' => ['primary'],
            'filled' => ['filled'],
            'outline' => ['outline'],
            'danger' => ['danger'],
            'ghost' => ['ghost'],
        ];
    }

    #[DataProvider('variants')]
    public function test_each_button_exposes_its_variant_without_shadow(string $variant): void
    {
        $html = (string) $this->blade('<flux:button :variant="$variant">Enregistrer</flux:button>', ['variant' => $variant]);

        $this->assertStringContainsString('data-variant="'.$variant.'"', $html);
        $this->assertStringNotContainsString('shadow-', $html);
    }

    #[DataProvider('variants')]
    public function test_only_the_primary_button_uses_the_accent(string $variant): void
    {
        $html = (string) $this->blade('<flux:button :variant="$variant">Enregistrer</flux:button>', ['variant' => $variant]);

        $isPrimary = $variant === 'primary';
        $this->assertSame($isPrimary, str_contains($html, '--color-accent)'));
        $this->assertSame($isPrimary, str_contains($html, '--color-accent-foreground'));
    }
}
