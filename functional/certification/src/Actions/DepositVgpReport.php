<?php

namespace Functional\Certification\Actions;

use Carbon\CarbonImmutable;
use Functional\Certification\Enums\CertificationHistoryEvent;
use Functional\Certification\Events\VgpReportDeposited;
use Functional\Certification\Exceptions\InvalidVgpReportException;
use Functional\Certification\History\CertificationHistory;
use Functional\Certification\Models\VgpReport;
use Functional\Fleet\Actions\UpdateMachineVgp;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Models\Machine;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class DepositVgpReport
{
    public function __construct(
        private readonly UpdateMachineVgp $updateMachineVgp,
        private readonly CertificationHistory $certificationHistory,
    ) {}

    public function handle(Authenticatable&AgencyMember $author, Machine $machine, UploadedFile $file, CarbonImmutable $verifiedOn, CarbonImmutable $dueOn): VgpReport
    {
        $this->ensureDepositable($machine, $file, $verifiedOn, $dueOn);

        $filePath = (string) $file->store('reports', config()->string('certification.reports_disk'));

        $report = DB::transaction(function () use ($author, $machine, $file, $filePath, $verifiedOn, $dueOn): VgpReport {
            $report = VgpReport::query()->create([
                'machine_id' => $machine->id,
                'file_path' => $filePath,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => (string) $file->getMimeType(),
                'size_bytes' => (int) $file->getSize(),
                'verified_on' => $verifiedOn,
                'due_on' => $dueOn,
                'deposited_by' => $author->getAuthIdentifier(),
            ]);
            $this->updateMachineVgp->handle($machine, $dueOn);
            $this->certificationHistory->record($machine, CertificationHistoryEvent::VgpReportDeposited, [
                'report_id' => $report->id,
                'verified_on' => $verifiedOn->format('d/m/Y'),
                'due_on' => $dueOn->format('d/m/Y'),
            ]);

            return $report;
        });

        VgpReportDeposited::dispatch($report);

        return $report;
    }

    private function ensureDepositable(Machine $machine, UploadedFile $file, CarbonImmutable $verifiedOn, CarbonImmutable $dueOn): void
    {
        $extension = mb_strtolower($file->getClientOriginalExtension());
        $sizeKilobytes = (int) ceil((int) $file->getSize() / 1024);

        match (true) {
            ! $machine->is_subject_to_vgp => throw InvalidVgpReportException::notSubjectToVgp($machine),
            ! in_array($extension, config()->array('certification.accepted_mimes'), true) => throw InvalidVgpReportException::unacceptedFormat($extension),
            $sizeKilobytes > config()->integer('certification.max_report_kilobytes') => throw InvalidVgpReportException::tooLarge($sizeKilobytes),
            ! $dueOn->gt($verifiedOn) => throw InvalidVgpReportException::dueDateNotAfterVerification(),
            default => null,
        };
    }
}
