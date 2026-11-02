<?php

namespace App\Models;

use Database\Factories\ActivityDirectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An instruction in a follow directions activity: the pictures to touch, in order.
 *
 * @property int $id
 * @property int $activity_id
 * @property string $text
 * @property list<int> $item_ids
 * @property int $position
 */
#[Fillable([
    'text',
    'item_ids',
    'position',
])]
class ActivityDirection extends Model
{
    /** @use HasFactory<ActivityDirectionFactory> */
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
            'item_ids' => 'array',
            'position' => 'integer',
        ];
    }

    /**
     * Get the activity the direction belongs to.
     *
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Get the number of pictures the child must touch.
     */
    public function stepCount(): int
    {
        return count($this->item_ids);
    }
}
