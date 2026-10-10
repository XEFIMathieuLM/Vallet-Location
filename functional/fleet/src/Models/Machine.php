<?php

namespace Functional\Fleet\Models;

use Carbon\CarbonImmutable;
use Functional\Fleet\Activity\RecordsAuthorAgency;
use Functional\Fleet\Database\Factories\MachineFactory;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\States\MachineState;
use Functional\Fleet\States\MachineStateFactory;
use Functional\Fleet\Vgp\VgpCompliance;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Lomkit\Access\Controls\HasControl;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $reference
 * @property int $machine_category_id
 * @property int $agency_id
 * @property MachineStatus $status
 * @property bool $is_subject_to_vgp
 * @property CarbonImmutable|null $vgp_due_date
 * @property-read MachineCategory $category
 * @property-read Agency $agency
 */
#[Fillable(['reference', 'machine_category_id', 'agency_id', 'status', 'is_subject_to_vgp', 'vgp_due_date'])]
#[UseFactory(MachineFactory::class)]
class Machine extends Model
{
    /** @use HasFactory<MachineFactory> */
    use HasControl, HasFactory, LogsActivity, RecordsAuthorAgency;

    protected function casts(): array
    {
        return [
            'status' => MachineStatus::class,
            'is_subject_to_vgp' => 'boolean',
            'vgp_due_date' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsTo<MachineCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MachineCategory::class, 'machine_category_id');
    }

    /**
     * @return BelongsTo<Agency, $this>
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function state(): MachineState
    {
        return MachineStateFactory::fromStatus($this->status);
    }

    public function isVgpCompliantUntil(CarbonImmutable $endDate): bool
    {
        return (new VgpCompliance($this->is_subject_to_vgp, $this->vgp_due_date))->coversUntil($endDate);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'vgp_due_date', 'agency_id'])
            ->logOnlyDirty();
    }

    /**
     * @return Attribute<string, string>
     */
    protected function reference(): Attribute
    {
        return Attribute::make(
            set: fn (string $reference): string => Str::upper(trim($reference)),
        );
    }
}
