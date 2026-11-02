<?php

namespace App\Models;

use App\Enums\ClinicalHours\ClinicMode;
use App\Enums\ClinicalHours\HoursType;
use App\Enums\ClinicalHours\LogMode;
use App\Enums\ClinicalHours\PlacementType;
use App\Enums\ClinicalHours\PracticumType;
use Database\Factories\ClinicalLogFormFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property LogMode $mode
 * @property int $year
 * @property int $semester
 * @property int $session_start_year
 * @property array<int, string>|null $days
 * @property PlacementType|null $placement
 * @property ClinicMode|null $clinic_mode
 * @property string|null $placement_name
 * @property string|null $supervisor_name
 * @property PracticumType $practicum_type
 * @property HoursType $hours_type
 * @property int|null $simple_minutes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $entries_count
 * @property-read int|string|null $entries_sum_duration_minutes
 */
#[Fillable([
    'mode',
    'year',
    'semester',
    'session_start_year',
    'days',
    'placement',
    'clinic_mode',
    'placement_name',
    'supervisor_name',
    'practicum_type',
    'hours_type',
    'simple_minutes',
])]
class ClinicalLogForm extends Model
{
    /** @use HasFactory<ClinicalLogFormFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => LogMode::class,
            'year' => 'integer',
            'semester' => 'integer',
            'session_start_year' => 'integer',
            'days' => 'array',
            'placement' => PlacementType::class,
            'clinic_mode' => ClinicMode::class,
            'practicum_type' => PracticumType::class,
            'hours_type' => HoursType::class,
            'simple_minutes' => 'integer',
        ];
    }

    /**
     * Get the student who owns the form.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the logged sessions on the form, in the order they happened.
     *
     * @return HasMany<ClinicalLogEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(ClinicalLogEntry::class)->orderBy('date')->orderBy('id');
    }

    /**
     * Determine whether the hours were keyed in directly instead of logged per session.
     */
    public function isSimple(): bool
    {
        return $this->mode === LogMode::Simple;
    }

    /**
     * Get the minutes the form counts toward the tracker.
     *
     * Log form mode requires the form to be loaded with `withSum('entries', 'duration_minutes')`.
     */
    public function totalMinutes(): int
    {
        return $this->isSimple()
            ? (int) $this->simple_minutes
            : (int) $this->entries_sum_duration_minutes;
    }

    /**
     * Get the academic session label, e.g. "2021/2022".
     */
    public function sessionLabel(): string
    {
        return $this->session_start_year.'/'.($this->session_start_year + 1);
    }
}
