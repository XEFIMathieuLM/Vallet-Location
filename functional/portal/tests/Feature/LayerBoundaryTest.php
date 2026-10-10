<?php

namespace Functional\Portal\Tests\Feature;

use Illuminate\Support\Facades\File;
use SplFileInfo;
use Tests\TestCase;

class LayerBoundaryTest extends TestCase
{
    public function test_no_other_layer_references_portal(): void
    {
        $offendingFiles = collect(File::directories(base_path('functional')))
            ->reject(fn (string $layerPath): bool => basename($layerPath) === 'portal')
            ->flatMap(fn (string $layerPath): array => File::allFiles($layerPath))
            ->filter(fn (SplFileInfo $file): bool => str_contains((string) file_get_contents($file->getPathname()), 'Functional\\Portal'))
            ->map(fn (SplFileInfo $file): string => $file->getPathname())
            ->values()
            ->all();

        $this->assertSame([], $offendingFiles);
    }

    public function test_portal_only_depends_on_the_layers_below_it(): void
    {
        $offendingFiles = collect(File::allFiles(base_path('functional/portal/src')))
            ->filter(fn (SplFileInfo $file): bool => preg_match('/Functional\\\\(Billing|Deposit|Accounts|Sales|Inspection)\\\\|App\\\\/', (string) file_get_contents($file->getPathname())) === 1)
            ->map(fn (SplFileInfo $file): string => $file->getPathname())
            ->values()
            ->all();

        $this->assertSame([], $offendingFiles);
    }
}
