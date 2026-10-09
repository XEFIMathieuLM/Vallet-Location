<?php

namespace Functional\Booking\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Booking\Database\Factories\ReservationFactory;
use Functional\Booking\Enums\ConflictReason;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lomkit\Access\Controls\HasControl;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $machine_id
 * @property int $customer_id
 * @property int $agency_id
 * @property int $created_by
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $end_date
 * @property CarbonImmutable $planned_end_date
 * @property ReservationStatus $status
 * @property CarbonImmutable|null $departed_at
 * @property CarbonImmutable|null $returned_at
 * @property ConflictReason|null $conflict_reason
 * @property-read Machine $machine
 * @property-read Customer $customer
 * @property-read Agency $agency
 * @property-read User $author
 */
#[Fillable([
    'machine_id', 'customer_id', 'agency_id', 'created_by', 'start_date', 'end_date',
    'planned_end_date', 'status', 'departed_at', 'returned_at', 'conflict_reason',
])]
#[UseFactory(ReservationFactory::class)]
class Reservation extends Model
{
    /** @use HasFactory<ReservationFactory> */
    use HasControl, HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
            'planned_end_date' => 'immutable_date',
            'status' => ReservationStatus::class,
            'departed_at' => 'immutable_datetime',
            'returned_at' => 'immutable_datetime',
            'conflict_reason' => ConflictReason::class,
        ];
    }

    /**
     * @return BelongsTo<Machine, $this>
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Agency, $this>
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'start_date', 'end_date', 'conflict_reason'])
            ->logOnlyDirty();
    }
}
