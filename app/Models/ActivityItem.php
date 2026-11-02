<?php

namespace App\Models;

use Database\Factories\ActivityItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A word in an activity, with its contrasting word for minimal pairs
 * or the story it belongs to for story sequences.
 *
 * @property int $id
 * @property int $activity_id
 * @property int $content_item_id
 * @property int|null $paired_item_id
 * @property int|null $group_number
 * @property int $position
 */
#[Fillable([
    'content_item_id',
    'paired_item_id',
    'group_number',
    'position',
])]
class ActivityItem extends Model
{
    /** @use HasFactory<ActivityItemFactory> */
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'group_number' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * Get the activity the word belongs to.
     *
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Get the word.
     *
     * @return BelongsTo<ContentItem, $this>
     */
    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class);
    }

    /**
     * Get the contrasting word for minimal pairs.
     *
     * @return BelongsTo<ContentItem, $this>
     */
    public function pairedItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class, 'paired_item_id');
    }
}
