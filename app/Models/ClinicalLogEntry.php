<?php

namespace App\Models;

use App\Enums\ClinicalHours\AgeGroup;
use App\Enums\ClinicalHours\SessionMode;
use App\Enums\ClinicalHours\SessionType;
use Database\Factories\ClinicalLogEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $clinical_log_form_id
 * @property Carbon $date
 * @property AgeGroup $age_group
 * @property string|null $rn_no
 * @property SessionMode $session_mode
 * @property SessionType $session_type
 * @property string|null $diagnosis
 * @property int $duration_minutes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'date',
    'age_group',
    'rn_no',
    'session_mode',
    'session_type',
    'diagnosis',
    'duration_minutes',
])]
class ClinicalLogEntry extends Model
{
    /** @use HasFactory<ClinicalLogEntryFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'age_group' => AgeGroup::class,
            'session_mode' => SessionMode::class,
            'session_type' => SessionType::class,
            'duration_minutes' => 'integer',
        ];
    }

    /**
     * Get the form the session was logged on.
     *
     * @return BelongsTo<ClinicalLogForm, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(ClinicalLogForm::class, 'clinical_log_form_id');
    }

    /**
     * Get the game sessions that were added to the log as this entry.
     *
     * @return HasMany<PlaySession, $this>
     */
    public function playSessions(): HasMany
    {
        return $this->hasMany(PlaySession::class);
    }
}
