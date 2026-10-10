<?php

namespace Functional\Fleet\Actions;

use Functional\Fleet\Data\FleetImportReport;
use Functional\Fleet\Data\MachineAttributes;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Events\FleetImported;
use Functional\Fleet\Import\FleetImportLineParser;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Uniqueness\MachineReferences;
use Spatie\SimpleExcel\SimpleExcelReader;

final class ImportFleet
{
    private const FIRST_DATA_LINE = 2;

    public function handle(string $filePath, string $fileType = ''): FleetImportReport
    {
        $existingReferences = Machine::query()
            ->selectRaw('upper(btrim(reference)) as normalized_reference')
            ->pluck('normalized_reference')
            ->flip();
        $lineParser = new FleetImportLineParser;
        $createdCount = 0;
        $rejections = [];

        $rows = SimpleExcelReader::create($filePath, $fileType)
            ->useDelimiter(';')
            ->preserveEmptyRows()
            ->getRows();

        /** @var array<string, mixed> $row */
        foreach ($rows as $rowIndex => $row) {
            if (array_filter($row, fn (mixed $cellValue): bool => trim((string) $cellValue) !== '') === []) {
                continue;
            }

            $reference = MachineReferences::normalize((string) ($row['reference'] ?? ''));
            $parsedLine = $lineParser->parse($row, $existingReferences->has($reference));

            if (! $parsedLine instanceof MachineAttributes) {
                $rejections[] = ['line' => $rowIndex + self::FIRST_DATA_LINE, 'reference' => $reference, 'reason' => $parsedLine];

                continue;
            }

            $this->createMachine($parsedLine);
            $createdCount++;
        }

        FleetImported::dispatch($createdCount);

        return new FleetImportReport($createdCount, $rejections);
    }

    private function createMachine(MachineAttributes $attributes): void
    {
        Machine::query()->create([
            'reference' => $attributes->reference,
            'machine_category_id' => $attributes->categoryId,
            'agency_id' => $attributes->agencyId,
            'status' => MachineStatus::Available,
            'is_subject_to_vgp' => $attributes->isSubjectToVgp,
            'vgp_due_date' => $attributes->vgpDueDate,
        ]);
    }
}
