<?php

namespace App\Models;

use Database\Factories\PlaySessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One run of an activity with a client.
 *
 * @property int $id
 * @property int $client_id
 * @property int $activity_id
 * @property int|null $clinical_log_entry_id
 * @property string|null $client_uuid
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property string|null $notes
 * @property list<string>|null $stickers
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'activity_id',
    'client_uuid',
    'started_at',
    'ended_at',
    'notes',
    'stickers',
])]
class PlaySession extends Model
{
    /** @use HasFactory<PlaySessionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'stickers' => 'array',
        ];
    }

    /**
     * Get the client who played.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the activity that was played.
     *
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Get the clinical log entry the session was added to.
     *
     * @return BelongsTo<ClinicalLogEntry, $this>
     */
    public function clinicalLogEntry(): BelongsTo
    {
        return $this->belongsTo(ClinicalLogEntry::class);
    }

    /**
     * Get the recorded answers, in the order they were given.
     *
     * @return HasMany<Trial, $this>
     */
    public function trials(): HasMany
    {
        return $this->hasMany(Trial::class)->orderBy('id');
    }

    /**
     * Determine whether the session has been ended.
     */
    public function isFinished(): bool
    {
        return $this->ended_at !== null;
    }
}
