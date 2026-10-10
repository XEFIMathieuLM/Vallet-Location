<?php

namespace Functional\Certification\Tests\Feature;

use Illuminate\Support\Facades\File;
use SplFileInfo;
use Tests\TestCase;

class LayerBoundaryTest extends TestCase
{
    public function test_no_other_layer_references_certification(): void
    {
        $offendingFiles = collect(['fleet', 'booking', 'inspection', 'billing'])
            ->flatMap(fn (string $layer): array => File::allFiles(base_path("functional/{$layer}")))
            ->filter(fn (SplFileInfo $file): bool => str_contains((string) file_get_contents($file->getPathname()), 'Functional\\Certification'))
            ->map(fn (SplFileInfo $file): string => $file->getPathname())
            ->values()
            ->all();

        $this->assertSame([], $offendingFiles);
    }

    public function test_certification_never_writes_machines_or_customers_itself(): void
    {
        $offendingFiles = collect(File::allFiles(base_path('functional/certification/src')))
            ->filter(fn (SplFileInfo $file): bool => preg_match('/\$(machine|customer)\w*->(update|save|fill|delete)\(|(Machine|Customer)::query\(\)->[^;]*->(update|delete)\(/i', (string) file_get_contents($file->getPathname())) === 1)
            ->map(fn (SplFileInfo $file): string => $file->getPathname())
            ->values()
            ->all();

        $this->assertSame([], $offendingFiles);
    }
}
