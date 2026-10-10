<?php

namespace Functional\Fleet\Livewire;

use Functional\Fleet\Actions\ImportFleet;
use Functional\Fleet\Data\FleetImportReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportFleetForm extends Component
{
    use WithFileUploads;

    public ?UploadedFile $fleetFile = null;

    public ?int $createdCount = null;

    /**
     * @var list<array{line: int, reference: string, reason: string}>
     */
    public array $rejections = [];

    public function import(ImportFleet $importFleet): void
    {
        $this->validate(
            ['fleetFile' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240']],
            attributes: ['fleetFile' => __('fleet::machines.import.file')],
        );

        /** @var UploadedFile $fleetFile */
        $fleetFile = $this->fleetFile;
        $fileType = $fleetFile->getClientOriginalExtension() === 'xlsx' ? 'xlsx' : 'csv';
        $report = $importFleet->handle($fleetFile->getRealPath(), $fileType);

        $this->showReport($report);
        $this->reset('fleetFile');
    }

    public function render(): View
    {
        return view('fleet::livewire.import-fleet-form')->title(__('fleet::machines.import.title'));
    }

    private function showReport(FleetImportReport $report): void
    {
        $this->createdCount = $report->createdCount;
        $this->rejections = $report->rejections;
    }
}
