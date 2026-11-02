<?php

namespace App\Models;

use App\Enums\Therapy\GameType;
use App\Enums\Therapy\GoalStatus;
use App\Enums\Therapy\Language;
use App\Enums\Therapy\WordPosition;
use Database\Factories\TherapyGoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A target the client should reach, e.g. 80% correct on initial /s/ across 3 sessions in a row.
 *
 * @property int $id
 * @property int $client_id
 * @property string $description
 * @property GameType|null $game_type
 * @property Language|null $language
 * @property string|null $target_sound
 * @property WordPosition|null $word_position
 * @property int $target_accuracy
 * @property int $consecutive_sessions
 * @property int $min_trials_per_session
 * @property GoalStatus $status
 * @property Carbon|null $achieved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'description',
    'game_type',
    'language',
    'target_sound',
    'word_position',
    'target_accuracy',
    'consecutive_sessions',
    'min_trials_per_session',
])]
class TherapyGoal extends Model
{
    /** @use HasFactory<TherapyGoalFactory> */
    use HasFactory;

    /**
     * The criteria that decide which answers count toward the goal and when it is reached.
     *
     * @var list<string>
     */
    public const array CRITERIA = [
        'game_type',
        'language',
        'target_sound',
        'word_position',
        'target_accuracy',
        'consecutive_sessions',
        'min_trials_per_session',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => GoalStatus::Active,
    ];

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
            'word_position' => WordPosition::class,
            'target_accuracy' => 'integer',
            'consecutive_sessions' => 'integer',
            'min_trials_per_session' => 'integer',
            'status' => GoalStatus::class,
            'achieved_at' => 'datetime',
        ];
    }

    /**
     * Get the client working toward the goal.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Determine whether the goal is still being worked on.
     */
    public function isActive(): bool
    {
        return $this->status === GoalStatus::Active;
    }

    /**
     * Mark the goal as reached now.
     */
    public function markAchieved(): void
    {
        $this->forceFill([
            'status' => GoalStatus::Achieved,
            'achieved_at' => now(),
        ])->save();
    }
}
