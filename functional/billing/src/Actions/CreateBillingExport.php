<?php

namespace Functional\Billing\Actions;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Billing\Calendar\BillingCalendar;
use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Exceptions\NothingToExportException;
use Functional\Billing\Exports\ExportLineFormatter;
use Functional\Billing\Models\BillingExport;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Transmissions\TransmissionLifecycle;
use Illuminate\Database\Eloquent\Builder;
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
                ->where(fn (Builder $unreservedTransmissions): Builder => $unreservedTransmissions
                    ->whereNull('reserved_until')
                    ->orWhere('reserved_until', '<=', CarbonImmutable::now()))
                ->with(MakeBillableLine::RELATIONS)
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
