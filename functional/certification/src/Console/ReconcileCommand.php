<?php

namespace Functional\Certification\Console;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Actions\OpenReservationCertificate;
use Functional\Certification\Calendar\CertificationCalendar;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Jobs\SendCertificateJob;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Queries\ReportInForce;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class ReconcileCommand extends Command
{
    protected $signature = 'certification:reconcile';

    protected $description = 'Open the missing VGP certificates and resend the pending ones that are due';

    public function handle(CertificationCalendar $certificationCalendar, OpenReservationCertificate $openReservationCertificate, ReportInForce $reportInForce): int
    {
        $certificationCalendar->goLiveDate();

        if (! $certificationCalendar->isLive()) {
            return self::SUCCESS;
        }

        Reservation::query()
            ->where('status', ReservationStatus::Confirmed)
            ->whereHas('machine', fn (Builder $machines): Builder => $machines->where('is_subject_to_vgp', true))
            ->whereNotExists(fn (QueryBuilder $certificates) => $certificates
                ->selectRaw('1')
                ->from('reservation_certificates')
                ->whereColumn('reservation_certificates.reservation_id', 'reservations.id'))
            ->with(['machine', 'customer'])
            ->chunkById(100, function (Collection $reservations) use ($openReservationCertificate, $reportInForce): void {
                $reportsInForce = $reportInForce->forMachines($reservations->pluck('machine_id'));
                $reservations->each(fn (Reservation $reservation) => $openReservationCertificate->handleWithReport($reservation, $reportsInForce->get($reservation->machine_id)));
            });

        ReservationCertificate::query()
            ->where('status', CertificateStatus::Pending)
            ->where(fn (Builder $dueCertificates): Builder => $dueCertificates
                ->whereNull('next_attempt_at')
                ->orWhere('next_attempt_at', '<=', CarbonImmutable::now()))
            ->pluck('id')
            ->each(fn (int $certificateId) => SendCertificateJob::dispatch($certificateId));

        return self::SUCCESS;
    }
}
