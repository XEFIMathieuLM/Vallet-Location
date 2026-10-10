<?php

namespace Functional\Portal\Http\Controllers;

use Functional\Booking\Models\Reservation;
use Functional\Portal\Models\CustomerAccount;
use Functional\Portal\Queries\AccountCertificateDocuments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CustomerCertificateDownloadController
{
    public function __invoke(Request $request, int $reservation, AccountCertificateDocuments $accountCertificateDocuments): StreamedResponse
    {
        $account = $request->user('customer');
        abort_unless($account instanceof CustomerAccount && $account->isAttached(), Response::HTTP_NOT_FOUND);

        $ownReservation = Reservation::query()->whereKey($reservation)->where('customer_id', $account->customer_id)->firstOrFail();
        $deliveredReport = $accountCertificateDocuments->deliveredReportOf($ownReservation);
        abort_if($deliveredReport === null, Response::HTTP_NOT_FOUND);

        return Storage::disk(config()->string('certification.reports_disk'))->download($deliveredReport->file_path, $deliveredReport->attachmentName());
    }
}
