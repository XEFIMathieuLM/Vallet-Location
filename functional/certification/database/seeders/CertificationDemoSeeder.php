<?php

namespace Functional\Certification\Database\Seeders;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Enums\DispatchChannel;
use Functional\Certification\Enums\DispatchFailureReason;
use Functional\Certification\Enums\DispatchOutcome;
use Functional\Certification\Models\CertificateDispatch;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Models\VgpReport;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class CertificationDemoSeeder extends Seeder
{
    private const DEMO_STATUSES = [
        CertificateStatus::Sent,
        CertificateStatus::AwaitingEmail,
        CertificateStatus::Failed,
        CertificateStatus::Pending,
        CertificateStatus::HandDelivered,
        CertificateStatus::AwaitingReport,
    ];

    public function run(): void
    {
        $employee = $this->userModel()::query()->orderBy('id')->firstOrFail();
        $reservations = Reservation::query()
            ->where('status', ReservationStatus::Confirmed)
            ->whereHas('machine', fn (Builder $machines): Builder => $machines->where('is_subject_to_vgp', true))
            ->with(['customer', 'machine'])
            ->orderBy('start_date')
            ->limit(count(self::DEMO_STATUSES))
            ->get()
            ->values();
        $machinesWithReport = Machine::query()->where('is_subject_to_vgp', true)
            ->whereIn('id', $reservations->filter(fn (Reservation $reservation, int $position): bool => self::DEMO_STATUSES[$position] !== CertificateStatus::AwaitingReport)->pluck('machine_id'))
            ->orWhere(fn (Builder $otherMachines): Builder => $otherMachines->where('is_subject_to_vgp', true)->whereNotIn('id', $reservations->pluck('machine_id'))->whereRaw('id % 2 = 0'))
            ->get();
        $reports = $machinesWithReport->mapWithKeys(fn (Machine $machine): array => [
            $machine->id => VgpReport::factory()->for($machine)->create(['deposited_by' => $employee->getKey()]),
        ]);

        $reservations->each(fn (Reservation $reservation, int $position) => $this->certificate($reservation, self::DEMO_STATUSES[$position], $reports->get($reservation->machine_id), $employee));
    }

    private function certificate(Reservation $reservation, CertificateStatus $status, ?VgpReport $report, Model $employee): void
    {
        $demoStatus = $report === null ? CertificateStatus::AwaitingReport : $status;
        $certificate = ReservationCertificate::query()->updateOrCreate(['reservation_id' => $reservation->id], [
            'status' => $demoStatus,
            'attempts' => $demoStatus === CertificateStatus::Failed ? 1 : 0,
            'last_failure_reason' => $demoStatus === CertificateStatus::Failed ? DispatchFailureReason::InvalidAddress : null,
            'status_changed_at' => CarbonImmutable::now()->subHours(2),
            'delivered_at' => $demoStatus->isDelivered() ? CarbonImmutable::now()->subHours(2) : null,
        ]);
        $certificate->dispatches()->delete();

        if ($report === null || ! in_array($certificate->status, [CertificateStatus::Sent, CertificateStatus::Failed, CertificateStatus::HandDelivered], true)) {
            return;
        }

        $isHandDelivery = $certificate->status === CertificateStatus::HandDelivered;
        CertificateDispatch::factory()->for($certificate, 'certificate')->for($report, 'report')->create([
            'channel' => $isHandDelivery ? DispatchChannel::Hand : DispatchChannel::Email,
            'recipient_email' => $isHandDelivery ? null : ($reservation->customer->email ?? faker()->email()),
            'is_automatic' => ! $isHandDelivery,
            'author_id' => $isHandDelivery ? $employee->getKey() : null,
            'outcome' => $certificate->status === CertificateStatus::Failed ? DispatchOutcome::Failed : DispatchOutcome::Sent,
            'failure_reason' => $certificate->status === CertificateStatus::Failed ? DispatchFailureReason::InvalidAddress : null,
        ]);
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        return config('auth.providers.users.model');
    }
}
