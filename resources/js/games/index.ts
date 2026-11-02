import type { Component } from 'vue';
import type { GameTypeValue } from '@/types';
import AnimalSound from './AnimalSound.vue';
import BalloonVoice from './BalloonVoice.vue';
import FindIt from './FindIt.vue';
import FollowDirections from './FollowDirections.vue';
import MinimalPairs from './MinimalPairs.vue';
import SortIt from './SortIt.vue';
import SoundDrill from './SoundDrill.vue';
import StorySequence from './StorySequence.vue';

/**
 * Game components keyed by the GameType enum value. Every game receives
 * `prompt`, `showWordText` and `disabled`, and emits `answer` with a result,
 * optional trial meta and the `performance.now()` time the child answered
 * (so a feedback pause is not counted in the response time).
 */
export const games: Record<GameTypeValue, Component> = {
    sound_drill: SoundDrill,
    minimal_pairs: MinimalPairs,
    find_it: FindIt,
    story_sequence: StorySequence,
    sort_it: SortIt,
    follow_directions: FollowDirections,
    balloon_voice: BalloonVoice,
    animal_sound: AnimalSound,
};
