<?php

namespace Functional\Billing\Http\Controllers;

use Functional\Billing\Models\BillingExport;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BillingExportDownloadController
{
    public function __invoke(BillingExport $billingExport): StreamedResponse
    {
        return Storage::disk(config()->string('billing.export_disk'))->download($billingExport->file_path);
    }
}
