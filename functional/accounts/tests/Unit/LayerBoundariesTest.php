<?php

namespace Functional\Accounts\Tests\Unit;

use Illuminate\Support\Facades\File;
use SplFileInfo;
use Tests\TestCase;

class LayerBoundariesTest extends TestCase
{
    public function test_no_lower_layer_references_accounts(): void
    {
        $offendingFiles = collect(['billing', 'inspection', 'booking', 'fleet'])
            ->flatMap(fn (string $layer): array => File::allFiles(base_path("functional/{$layer}")))
            ->filter(fn (SplFileInfo $file): bool => str_contains((string) file_get_contents($file->getPathname()), 'Functional\\Accounts'))
            ->map(fn (SplFileInfo $file): string => $file->getPathname())
            ->values()
            ->all();

        $this->assertSame([], $offendingFiles);
    }
}
