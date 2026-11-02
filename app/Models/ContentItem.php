<?php

namespace App\Models;

use App\Enums\Therapy\Language;
use App\Enums\Therapy\WordPosition;
use Database\Factories\ContentItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * A word with its picture, used to build activities.
 *
 * Items without an owner form the read-only starter library shared by every clinician.
 *
 * @property int $id
 * @property int|null $user_id
 * @property Language $language
 * @property string $word
 * @property string|null $emoji
 * @property string|null $image_path
 * @property string|null $target_sound
 * @property WordPosition|null $word_position
 * @property string|null $category
 * @property string|null $pair_key
 * @property string|null $story_key
 * @property int|null $story_position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'language',
    'word',
    'emoji',
    'image_path',
    'target_sound',
    'word_position',
    'category',
])]
class ContentItem extends Model
{
    /** @use HasFactory<ContentItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'language' => Language::class,
            'word_position' => WordPosition::class,
            'story_position' => 'integer',
        ];
    }

    /**
     * Get the clinician who added the word, if it is not a starter word.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine whether the word belongs to the shared starter library.
     */
    public function isStarter(): bool
    {
        return $this->user_id === null;
    }

    /**
     * Get the public URL of the uploaded picture.
     */
    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /**
     * Get the fields a game needs to show the word as a picture card.
     *
     * @return array{id: int, word: string, emoji: string|null, image_url: string|null}
     */
    public function toPicture(): array
    {
        return [
            'id' => $this->id,
            'word' => $this->word,
            'emoji' => $this->emoji,
            'image_url' => $this->imageUrl(),
        ];
    }

    /**
     * Scope the query to starter words and the given clinician's own words.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        $query->where(fn (Builder $query) => $query->whereNull('user_id')->orWhere('user_id', $user->id));
    }
}
