<?php

namespace Functional\Certification\Database\Factories;

use Carbon\CarbonImmutable;
use Functional\Certification\Models\VgpReport;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @extends Factory<VgpReport>
 */
class VgpReportFactory extends Factory
{
    protected $model = VgpReport::class;

    public function definition(): array
    {
        $verifiedOn = CarbonImmutable::today()->subDays(faker()->number(1, 30));

        return [
            'machine_id' => fn (): Factory => Machine::factory()->vgpValid(),
            'file_path' => fn (): string => $this->storedReportPath(),
            'original_name' => faker()->vgpReportFileName(),
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'verified_on' => $verifiedOn,
            'due_on' => $verifiedOn->addMonths(6),
            'deposited_by' => fn () => Factory::factoryForModel($this->userModel()),
        ];
    }

    private function storedReportPath(): string
    {
        $filePath = 'reports/'.Str::uuid()->toString().'.pdf';
        Storage::disk(config()->string('certification.reports_disk'))->put($filePath, '%PDF-1.4 rapport de VGP');

        return $filePath;
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        return config('auth.providers.users.model');
    }
}
