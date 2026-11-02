<?php

namespace App\Enums\Therapy;

use App\Concerns\HasEnumOptions;

enum GameType: string
{
    use HasEnumOptions;

    case SoundDrill = 'sound_drill';
    case MinimalPairs = 'minimal_pairs';
    case FindIt = 'find_it';
    case StorySequence = 'story_sequence';
    case SortIt = 'sort_it';
    case FollowDirections = 'follow_directions';
    case BalloonVoice = 'balloon_voice';

    /**
     * Get the label shown to the clinician.
     */
    public function label(): string
    {
        return match ($this) {
            self::SoundDrill => 'Sound card drill',
            self::MinimalPairs => 'Minimal pairs',
            self::FindIt => 'Find it!',
            self::StorySequence => 'Story sequence',
            self::SortIt => 'Sort it',
            self::FollowDirections => 'Follow directions',
            self::BalloonVoice => 'Balloon voice',
        };
    }

    /**
     * Get a short description of how the game is played.
     */
    public function description(): string
    {
        return match ($this) {
            self::SoundDrill => 'Articulation: the child names each picture and you score the attempt.',
            self::MinimalPairs => 'Phonology: you say one of two words and the child taps the matching picture.',
            self::FindIt => 'Receptive vocabulary: you say a word and the child finds its picture among several.',
            self::StorySequence => 'Language and narrative: the child puts the pictures of a short story in order.',
            self::SortIt => 'Word groups: the child drags each picture into the group it belongs to.',
            self::FollowDirections => 'Understanding instructions: you give a direction and the child touches the pictures in order.',
            self::BalloonVoice => 'Voice and loudness: the child keeps their voice in the target range to lift a balloon.',
        };
    }

    /**
     * Determine whether each activity item needs a second, contrasting word.
     */
    public function usesPairs(): bool
    {
        return $this === self::MinimalPairs;
    }

    /**
     * Determine whether the activity is built from words in the library.
     */
    public function usesWords(): bool
    {
        return $this !== self::BalloonVoice;
    }

    /**
     * Determine whether the child sorts pictures into word groups.
     */
    public function usesCategories(): bool
    {
        return $this === self::SortIt;
    }

    /**
     * Determine whether the activity has a list of directions to follow.
     */
    public function usesDirections(): bool
    {
        return $this === self::FollowDirections;
    }

    /**
     * Determine whether activity items are grouped into ordered stories.
     */
    public function usesStories(): bool
    {
        return $this === self::StorySequence;
    }
}
