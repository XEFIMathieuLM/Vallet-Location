<?php

namespace Functional\Certification\Models;

use Carbon\CarbonImmutable;
use Functional\Certification\Database\Factories\VgpReportFactory;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lomkit\Access\Controls\HasControl;

/**
 * @property int $id
 * @property int $machine_id
 * @property string $file_path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size_bytes
 * @property CarbonImmutable $verified_on
 * @property CarbonImmutable $due_on
 * @property int $deposited_by
 * @property CarbonImmutable $created_at
 * @property-read Machine $machine
 * @property-read Model&AgencyMember $depositor
 */
#[Fillable(['machine_id', 'file_path', 'original_name', 'mime_type', 'size_bytes', 'verified_on', 'due_on', 'deposited_by'])]
#[UseFactory(VgpReportFactory::class)]
class VgpReport extends Model
{
    /** @use HasFactory<VgpReportFactory> */
    use HasControl, HasFactory;

    protected function casts(): array
    {
        return [
            'verified_on' => 'immutable_date',
            'due_on' => 'immutable_date',
            'created_at' => 'immutable_datetime',
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
     * @return BelongsTo<Model, $this>
     */
    public function depositor(): BelongsTo
    {
        return $this->belongsTo($this->userModel(), 'deposited_by');
    }

    public function attachmentName(): string
    {
        return "VGP-{$this->machine->reference}-{$this->verified_on->format('Y-m-d')}.".pathinfo($this->original_name, PATHINFO_EXTENSION);
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        return config('auth.providers.users.model');
    }
}
