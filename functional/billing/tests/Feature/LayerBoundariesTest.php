<?php

namespace Functional\Billing\Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LayerBoundariesTest extends TestCase
{
    public function test_no_lower_layer_references_billing(): void
    {
        $offendingFiles = collect(['inspection', 'booking', 'fleet'])
            ->flatMap(fn (string $layer): array => File::allFiles(base_path("functional/{$layer}")))
            ->filter(fn (\SplFileInfo $file): bool => str_contains((string) file_get_contents($file->getPathname()), 'Functional\\Billing'))
            ->map(fn (\SplFileInfo $file): string => $file->getPathname())
            ->values()
            ->all();

        $this->assertSame([], $offendingFiles);
    }
}
