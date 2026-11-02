<?php

namespace App\Models;

use App\Enums\Therapy\TrialResult;
use Database\Factories\TrialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One answer given by the client during a session: one word, one whole story for story sequences,
 * one direction for follow directions, or one try for balloon voice.
 *
 * @property int $id
 * @property int $play_session_id
 * @property int|null $content_item_id
 * @property int|null $group_number
 * @property int|null $activity_direction_id
 * @property TrialResult $result
 * @property int|null $response_ms
 * @property array{attempts?: int, chosen_item_id?: int, chosen_category?: string, touched_item_ids?: list<int>, held_ms?: int, average_level?: float}|null $meta
 * @property string $client_uuid
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'content_item_id',
    'group_number',
    'activity_direction_id',
    'result',
    'response_ms',
    'meta',
    'client_uuid',
])]
class Trial extends Model
{
    /** @use HasFactory<TrialFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'result' => TrialResult::class,
            'response_ms' => 'integer',
            'group_number' => 'integer',
            'meta' => 'array',
        ];
    }

    /**
     * Get the session the answer was given in.
     *
     * @return BelongsTo<PlaySession, $this>
     */
    public function playSession(): BelongsTo
    {
        return $this->belongsTo(PlaySession::class);
    }

    /**
     * Get the direction the answer was for, in follow directions.
     *
     * @return BelongsTo<ActivityDirection, $this>
     */
    public function activityDirection(): BelongsTo
    {
        return $this->belongsTo(ActivityDirection::class);
    }

    /**
     * Get the target word of the answer. Story answers have no single word.
     *
     * @return BelongsTo<ContentItem, $this>
     */
    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class);
    }
}
