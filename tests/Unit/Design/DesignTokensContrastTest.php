<?php

namespace Tests\Unit\Design;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DesignTokensContrastTest extends TestCase
{
    private const WHITE = '#ffffff';

    private const BLACK = '#000000';

    /**
     * @return array<string, array{string, string, float}>
     */
    public static function textPairs(): array
    {
        $pairs = [
            'black on accent' => [self::BLACK, 'accent', 4.5],
            'black on white' => [self::BLACK, self::WHITE, 4.5],
            'black on surface' => [self::BLACK, 'zinc-50', 4.5],
            'mute on white' => ['zinc-500', self::WHITE, 4.5],
            'mute on surface' => ['zinc-500', 'zinc-50', 4.5],
            'white 72% on black' => ['white/72', self::BLACK, 4.5],
            'white 72% on deep' => ['white/72', 'zinc-900', 4.5],
            'white 55% on black' => ['white/55', self::BLACK, 4.5],
            'white on danger button' => [self::WHITE, 'red-600', 4.5],
            'black on workshop cell' => [self::BLACK, 'amber-300', 4.5],
            'black on out of order cell' => [self::BLACK, 'red-500', 4.5],
            'focus outline on white' => [self::BLACK, self::WHITE, 3.0],
            'focus outline on black' => [self::WHITE, self::BLACK, 3.0],
        ];

        foreach (['red', 'green', 'amber', 'blue'] as $scale) {
            $pairs["{$scale} badge on light"] = ["{$scale}-700", "{$scale}-400/20@".self::WHITE, 4.5];
            $pairs["{$scale} badge on dark"] = ["{$scale}-200", "{$scale}-400/40@zinc-900", 4.5];
            $pairs["{$scale} text on white"] = ["{$scale}-600", self::WHITE, 4.5];
        }

        return $pairs;
    }

    #[DataProvider('textPairs')]
    public function test_each_text_and_background_pair_reaches_its_contrast(string $foreground, string $background, float $minimumRatio): void
    {
        $backgroundColor = $this->resolve($background, self::WHITE);
        $ratio = $this->contrast($this->resolve($foreground, $backgroundColor), $backgroundColor);

        $this->assertGreaterThanOrEqual($minimumRatio, $ratio, sprintf('%s sur %s : %.2f', $foreground, $background, $ratio));
    }

    public function test_the_accent_is_never_readable_as_text_on_white(): void
    {
        $this->assertLessThan(3.0, $this->contrast($this->resolve('accent', self::WHITE), self::WHITE));
    }

    private function resolve(string $color, string $underlyingColor): string
    {
        if (str_contains($color, '@')) {
            [$color, $underlyingColor] = explode('@', $color);
            $underlyingColor = $this->resolve($underlyingColor, self::WHITE);
        }

        if (str_contains($color, '/')) {
            [$tokenName, $percentage] = explode('/', $color);

            return $this->mix($this->resolve($tokenName, $underlyingColor), $underlyingColor, (int) $percentage / 100);
        }

        if (str_starts_with($color, '#')) {
            return $color;
        }

        if ($color === 'white') {
            return self::WHITE;
        }

        return $this->tokens()[$color] ?? $this->fail("Jeton --color-{$color} absent de tokens.css");
    }

    /**
     * @return array<string, string>
     */
    private function tokens(): array
    {
        $stylesheet = (string) file_get_contents(__DIR__.'/../../../resources/css/vallet/tokens.css');
        preg_match_all('/--color-([a-z0-9-]+):\s*(#[0-9a-f]{6})\s*;/i', $stylesheet, $matches);

        return array_combine($matches[1], array_map('strtolower', $matches[2]));
    }

    private function mix(string $foreground, string $background, float $opacity): string
    {
        $channels = array_map(
            fn (int $foregroundChannel, int $backgroundChannel): int => (int) round($foregroundChannel * $opacity + $backgroundChannel * (1 - $opacity)),
            $this->channels($foreground),
            $this->channels($background),
        );

        return vsprintf('#%02x%02x%02x', $channels);
    }

    private function contrast(string $firstColor, string $secondColor): float
    {
        $luminances = [$this->luminance($firstColor), $this->luminance($secondColor)];

        return (max($luminances) + 0.05) / (min($luminances) + 0.05);
    }

    private function luminance(string $color): float
    {
        [$red, $green, $blue] = array_map(function (int $channel): float {
            $ratio = $channel / 255;

            return $ratio <= 0.04045 ? $ratio / 12.92 : (($ratio + 0.055) / 1.055) ** 2.4;
        }, $this->channels($color));

        return 0.2126 * $red + 0.7152 * $green + 0.0722 * $blue;
    }

    /**
     * @return array{int, int, int}
     */
    private function channels(string $color): array
    {
        return [(int) hexdec(substr($color, 1, 2)), (int) hexdec(substr($color, 3, 2)), (int) hexdec(substr($color, 5, 2))];
    }
}
