<?php

namespace App\Models;

use App\Enums\ClinicalHours\ClinicMode;
use App\Enums\ClinicalHours\HoursType;
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
 * @property int $year
 * @property int $semester
 * @property int $session_start_year
 * @property array<int, string> $days
 * @property PlacementType $placement
 * @property ClinicMode $clinic_mode
 * @property string $placement_name
 * @property string $supervisor_name
 * @property PracticumType $practicum_type
 * @property HoursType $hours_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $entries_count
 * @property-read int|string|null $entries_sum_duration_minutes
 */
#[Fillable([
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
            'year' => 'integer',
            'semester' => 'integer',
            'session_start_year' => 'integer',
            'days' => 'array',
            'placement' => PlacementType::class,
            'clinic_mode' => ClinicMode::class,
            'practicum_type' => PracticumType::class,
            'hours_type' => HoursType::class,
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
     * Get the academic session label, e.g. "2021/2022".
     */
    public function sessionLabel(): string
    {
        return $this->session_start_year.'/'.($this->session_start_year + 1);
    }
}
