<?php

namespace Functional\Certification\Models;

use Carbon\CarbonImmutable;
use Functional\Certification\Database\Factories\CertificateDispatchFactory;
use Functional\Certification\Enums\DispatchChannel;
use Functional\Certification\Enums\DispatchFailureReason;
use Functional\Certification\Enums\DispatchOutcome;
use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $reservation_certificate_id
 * @property int $vgp_report_id
 * @property DispatchChannel $channel
 * @property string|null $recipient_email
 * @property bool $is_automatic
 * @property int|null $author_id
 * @property DispatchOutcome $outcome
 * @property DispatchFailureReason|null $failure_reason
 * @property CarbonImmutable $attempted_at
 * @property-read ReservationCertificate $certificate
 * @property-read VgpReport $report
 * @property-read (Model&AgencyMember)|null $author
 */
#[Fillable(['reservation_certificate_id', 'vgp_report_id', 'channel', 'recipient_email', 'is_automatic', 'author_id', 'outcome', 'failure_reason', 'attempted_at'])]
#[UseFactory(CertificateDispatchFactory::class)]
class CertificateDispatch extends Model
{
    /** @use HasFactory<CertificateDispatchFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'channel' => DispatchChannel::class,
            'outcome' => DispatchOutcome::class,
            'failure_reason' => DispatchFailureReason::class,
            'is_automatic' => 'boolean',
            'attempted_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<ReservationCertificate, $this>
     */
    public function certificate(): BelongsTo
    {
        return $this->belongsTo(ReservationCertificate::class, 'reservation_certificate_id');
    }

    /**
     * @return BelongsTo<VgpReport, $this>
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(VgpReport::class, 'vgp_report_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo($this->userModel(), 'author_id');
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        return config('auth.providers.users.model');
    }
}
