<?php

namespace Functional\Portal\Queries;

use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Enums\DispatchOutcome;
use Functional\Certification\Models\CertificateDispatch;
use Functional\Certification\Models\VgpReport;
use Illuminate\Database\Eloquent\Builder;

final class AccountCertificateDocuments
{
    /**
     * @param  iterable<Reservation>  $reservations
     * @return array<int, int>
     */
    public function deliveredReportIdsByReservation(iterable $reservations): array
    {
        $reservationIds = collect($reservations)
            ->filter(fn (Reservation $reservation): bool => $reservation->status !== ReservationStatus::Cancelled && $reservation->machine->is_subject_to_vgp)
            ->map(fn (Reservation $reservation): int => $reservation->id)
            ->values()
            ->all();

        if ($reservationIds === []) {
            return [];
        }

        return $this->lastDeliveredDispatches($reservationIds)
            ->pluck('vgp_report_id', 'reservation_id')
            ->all();
    }

    public function deliveredReportOf(Reservation $reservation): ?VgpReport
    {
        if ($reservation->status === ReservationStatus::Cancelled) {
            return null;
        }

        $reportId = $this->lastDeliveredDispatches([$reservation->id])->value('vgp_report_id');

        return is_int($reportId) ? VgpReport::query()->with('machine')->whereKey($reportId)->first() : null;
    }

    /**
     * @param  array<int, int>  $reservationIds
     * @return Builder<CertificateDispatch>
     */
    private function lastDeliveredDispatches(array $reservationIds): Builder
    {
        return CertificateDispatch::query()
            ->join('reservation_certificates', 'reservation_certificates.id', '=', 'certificate_dispatches.reservation_certificate_id')
            ->whereIn('reservation_certificates.reservation_id', $reservationIds)
            ->where('certificate_dispatches.outcome', DispatchOutcome::Sent)
            ->selectRaw('distinct on (reservation_certificates.reservation_id) reservation_certificates.reservation_id, certificate_dispatches.vgp_report_id')
            ->orderBy('reservation_certificates.reservation_id')
            ->orderByDesc('certificate_dispatches.attempted_at')
            ->orderByDesc('certificate_dispatches.id');
    }
}
