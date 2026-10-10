<?php

namespace Functional\Fleet\Import;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Functional\Fleet\Data\MachineAttributes;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\MachineCategory;
use Functional\Fleet\Uniqueness\MachineReferences;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class FleetImportLineParser
{
    /**
     * @var Collection<string, MachineCategory>
     */
    private Collection $categoriesByName;

    /**
     * @var Collection<string, Agency>
     */
    private Collection $agenciesByName;

    /**
     * @var array<string, true>
     */
    private array $knownReferences;

    public function __construct()
    {
        $this->categoriesByName = MachineCategory::query()->get()->keyBy(fn (MachineCategory $category): string => self::nameKey($category->name));
        $this->agenciesByName = Agency::query()->get()->keyBy(fn (Agency $agency): string => self::nameKey($agency->name));
        $this->knownReferences = [];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function parse(array $row, bool $isExistingReference): MachineAttributes|string
    {
        $reference = MachineReferences::normalize((string) ($row['reference'] ?? ''));
        $categoryName = trim((string) ($row['categorie'] ?? ''));
        $agencyName = trim((string) ($row['agence'] ?? ''));
        $category = $this->categoriesByName->get(self::nameKey($categoryName));
        $agency = $this->agenciesByName->get(self::nameKey($agencyName));
        $vgpDueDate = $this->parseDate($row['echeance_vgp'] ?? '');

        $rejectionReason = match (true) {
            $reference === '' => __('fleet::machines.import.rejections.missing_reference'),
            isset($this->knownReferences[$reference]) => __('fleet::machines.import.rejections.duplicate_in_file'),
            $isExistingReference => __('fleet::machines.import.rejections.existing_reference'),
            $category === null => __('fleet::machines.import.rejections.unknown_category', ['category' => $categoryName]),
            $agency === null => __('fleet::machines.import.rejections.unknown_agency', ['agency' => $agencyName]),
            $vgpDueDate === false => __('fleet::machines.import.rejections.unreadable_date', ['date' => trim((string) $row['echeance_vgp'])]),
            default => null,
        };

        if ($reference !== '') {
            $this->knownReferences[$reference] = true;
        }

        if ($rejectionReason !== null || $category === null || $agency === null || $vgpDueDate === false) {
            return (string) $rejectionReason;
        }

        return new MachineAttributes(
            reference: $reference,
            categoryId: $category->id,
            agencyId: $agency->id,
            isSubjectToVgp: $category->is_vgp_required || $this->isSubjectToVgp($row['soumise_vgp'] ?? ''),
            vgpDueDate: $vgpDueDate,
        );
    }

    private function parseDate(mixed $cellValue): CarbonImmutable|false|null
    {
        if ($cellValue instanceof DateTimeInterface) {
            return CarbonImmutable::instance($cellValue)->startOfDay();
        }

        $dateText = trim((string) $cellValue);

        if ($dateText === '') {
            return null;
        }

        $parsedDate = CarbonImmutable::createFromFormat('!d/m/Y', $dateText);

        return $parsedDate !== null && $parsedDate->format('d/m/Y') === $dateText ? $parsedDate : false;
    }

    private function isSubjectToVgp(mixed $cellValue): bool
    {
        return Str::lower(trim((string) $cellValue)) === 'oui';
    }

    private static function nameKey(string $name): string
    {
        return Str::lower(trim($name));
    }
}
