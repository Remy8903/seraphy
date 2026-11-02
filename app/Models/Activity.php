<?php

namespace App\Models;

use App\Enums\Therapy\GameType;
use App\Enums\Therapy\Language;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * A game configured with the clinician's chosen words and settings.
 *
 * @property int $id
 * @property int $user_id
 * @property GameType $game_type
 * @property string $title
 * @property Language $language
 * @property array{trial_count: int, shuffle: bool, show_word_text: bool, reward_every?: int, sticker_set?: string, field_size?: int, speak_word?: bool, categories?: list<string>, target_level?: string, hold_seconds?: int} $settings
 * @property string|null $background_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'game_type',
    'title',
    'language',
    'settings',
])]
class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'game_type' => GameType::class,
            'language' => Language::class,
            'settings' => 'array',
        ];
    }

    /**
     * Get the public URL of the picture shown behind the game, if one was uploaded.
     */
    public function backgroundUrl(): ?string
    {
        return $this->background_path ? Storage::disk('public')->url($this->background_path) : null;
    }

    /**
     * Get the clinician who created the activity.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the words in the activity, in order.
     *
     * @return HasMany<ActivityItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ActivityItem::class)->orderBy('position');
    }

    /**
     * Get the instructions of a follow directions activity, in order.
     *
     * @return HasMany<ActivityDirection, $this>
     */
    public function directions(): HasMany
    {
        return $this->hasMany(ActivityDirection::class)->orderBy('position');
    }

    /**
     * Get the clients the activity is assigned to.
     *
     * @return BelongsToMany<Client, $this>
     */
    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'assignments')->withTimestamps();
    }

    /**
     * Get every game session played with the activity.
     *
     * @return HasMany<PlaySession, $this>
     */
    public function playSessions(): HasMany
    {
        return $this->hasMany(PlaySession::class);
    }
}
