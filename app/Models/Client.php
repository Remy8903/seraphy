<?php

namespace App\Models;

use App\Enums\ClinicalHours\AgeGroup;
use App\Enums\Therapy\Language;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A therapy client, identified only by the clinician's reference code.
 *
 * @property int $id
 * @property int $user_id
 * @property string $reference_code
 * @property AgeGroup $age_group
 * @property Language $language
 * @property string|null $diagnosis
 * @property string|null $notes
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'reference_code',
    'age_group',
    'language',
    'diagnosis',
    'notes',
])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'age_group' => AgeGroup::class,
            'language' => Language::class,
            'archived_at' => 'datetime',
        ];
    }

    /**
     * Get the clinician who works with the client.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the activities on the client's therapy plan.
     *
     * @return BelongsToMany<Activity, $this>
     */
    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'assignments')->withTimestamps();
    }

    /**
     * Get the client's game sessions, newest first.
     *
     * @return HasMany<PlaySession, $this>
     */
    public function playSessions(): HasMany
    {
        return $this->hasMany(PlaySession::class)->latest('started_at')->latest('id');
    }

    /**
     * Get the client's therapy goals, oldest first.
     *
     * @return HasMany<TherapyGoal, $this>
     */
    public function goals(): HasMany
    {
        return $this->hasMany(TherapyGoal::class)->orderBy('id');
    }

    /**
     * Determine whether the client has been archived.
     */
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * Scope the query to clients that have not been archived.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('archived_at');
    }
}
