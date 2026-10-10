<?php

namespace Functional\Billing\Actions;

use App\Models\User;
use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Exceptions\NothingToExportException;
use Functional\Billing\Models\BillingExport;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Support\BillingCalendar;
use Functional\Billing\Support\ExportLineFormatter;
use Functional\Billing\Support\TransmissionLifecycle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\SimpleExcel\SimpleExcelWriter;

final class CreateBillingExport
{
    public function __construct(
        private readonly BillingCalendar $billingCalendar,
        private readonly MakeBillableLine $makeBillableLine,
        private readonly ExportLineFormatter $exportLineFormatter,
        private readonly TransmissionLifecycle $transmissionLifecycle,
    ) {}

    public function handle(User $author): BillingExport
    {
        return DB::transaction(function () use ($author): BillingExport {
            $exportableTransmissions = Transmission::query()
                ->whereIn('status', [TransmissionStatus::Pending, TransmissionStatus::Failed])
                ->orderBy('created_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($exportableTransmissions->isEmpty()) {
                throw NothingToExportException::make();
            }

            $fileName = 'export-facturation-'.$this->billingCalendar->now()->format('Ymd-His').'.csv';
            $this->writeFile($fileName, $exportableTransmissions);

            $billingExport = BillingExport::query()->create([
                'created_by' => $author->id,
                'line_count' => $exportableTransmissions->count(),
                'file_path' => $fileName,
            ]);

            $exportableTransmissions->each(fn (Transmission $transmission) => $this->transmissionLifecycle->markExported($transmission, $billingExport));

            return $billingExport;
        });
    }

    /**
     * @param  Collection<int, Transmission>  $transmissions
     */
    private function writeFile(string $fileName, Collection $transmissions): void
    {
        $writer = SimpleExcelWriter::create(
            Storage::disk(config()->string('billing.export_disk'))->path($fileName),
            'csv',
            delimiter: ';',
            shouldAddBom: true,
        );

        $transmissions->each(fn (Transmission $transmission) => $writer->addRow(
            $this->exportLineFormatter->format($this->makeBillableLine->handle($transmission)),
        ));

        $writer->close();
    }
}
