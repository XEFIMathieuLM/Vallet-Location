<?php

namespace Functional\Certification\Http\Controllers;

use Functional\Certification\Models\VgpReport;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class VgpReportFileController
{
    public function __invoke(VgpReport $report): StreamedResponse
    {
        return Storage::disk(config()->string('certification.reports_disk'))->download($report->file_path, $report->attachmentName());
    }
}
