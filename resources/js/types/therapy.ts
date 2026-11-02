import type { Ref } from 'vue';
import type { SelectOption } from './clinical-hours';

export type GameTypeValue =
    | 'sound_drill'
    | 'minimal_pairs'
    | 'find_it'
    | 'story_sequence'
    | 'sort_it'
    | 'follow_directions'
    | 'balloon_voice';

export type VoiceLevelValue = 'quiet' | 'normal' | 'loud';

export type TrialResultValue = 'correct' | 'prompted' | 'incorrect';

export type Picture = {
    id: number;
    word: string;
    emoji: string | null;
    image_url: string | null;
};

export type ClientListItem = {
    id: number;
    reference_code: string;
    age_group: string;
    language: string;
    diagnosis: string | null;
    activities_count: number;
    last_played_at: string | null;
    is_archived: boolean;
};

export type ClientInput = {
    reference_code: string;
    age_group: string;
    language: string;
    diagnosis: string;
    notes: string;
};

export type ClientFormOptions = {
    ageGroups: SelectOption[];
    languages: SelectOption[];
};

export type ClientDetails = {
    id: number;
    reference_code: string;
    age_group: string;
    language: string;
    diagnosis: string | null;
    notes: string | null;
    is_archived: boolean;
};

export type AssignedActivity = {
    id: number;
    title: string;
    game_type: GameTypeValue;
    game_type_label: string;
    language: string;
    items_count: number;
};

export type UnassignedActivity = {
    id: number;
    title: string;
    game_type_label: string;
    language: string;
};

export type ClientSessionListItem = {
    id: number;
    activity_title: string;
    started_at: string;
    date: string;
    is_finished: boolean;
    clinical_log_form_id: number | null;
    total: number;
    accuracy: number;
};

export type ResultTally = {
    correct: number;
    prompted: number;
    incorrect: number;
};

export type SessionAccuracy = ResultTally & {
    id: number;
    date: string;
    total: number;
    accuracy: number;
};

export type ActivityProgress = {
    activity_id: number;
    title: string;
    game_type: GameTypeValue;
    sessions: SessionAccuracy[];
};

export type SoundProgress = ResultTally & {
    target_sound: string;
    word_position: string | null;
    word_position_label: string | null;
    total: number;
    accuracy: number;
};

export type CategoryProgress = ResultTally & {
    category: string;
    total: number;
    accuracy: number;
};

export type StepProgress = ResultTally & {
    steps: number;
    total: number;
    accuracy: number;
};

export type ClientProgress = {
    activities: ActivityProgress[];
    sounds: SoundProgress[];
    categories: CategoryProgress[];
    steps: StepProgress[];
};

export type LibraryItem = Picture & {
    language: string;
    target_sound: string | null;
    word_position: string | null;
    word_position_label?: string | null;
    category: string | null;
    is_starter?: boolean;
    pair_key?: string | null;
    story_key?: string | null;
    story_position?: number | null;
};

export type GameTypeOption = SelectOption & {
    description: string;
    uses_pairs: boolean;
    uses_stories: boolean;
    uses_words: boolean;
    uses_categories: boolean;
    uses_directions: boolean;
};

export type StickerSetOption = SelectOption & {
    stickers: string[];
};

export type ActivityBuilderOptions = {
    gameTypes: GameTypeOption[];
    languages: SelectOption[];
    wordPositions: SelectOption[];
    stickerSets: StickerSetOption[];
    voiceLevels: SelectOption[];
};

export type ActivitySettings = {
    trial_count: number | string;
    shuffle: boolean;
    show_word_text: boolean;
    reward_every?: number | string;
    sticker_set?: string | null;
    field_size?: number | string;
    speak_word?: boolean;
    categories?: string[];
    target_level?: VoiceLevelValue;
    hold_seconds?: number | string;
};

export type DirectionInput = {
    id?: number | null;
    text: string;
    item_ids: number[];
};

export type ActivityItemInput = {
    content_item_id: number;
    paired_item_id: number | null;
    group_number?: number | null;
};

export type ActivityInput = {
    game_type: GameTypeValue;
    title: string;
    language: string;
    settings: ActivitySettings;
    items: ActivityItemInput[];
    directions: DirectionInput[];
    /** A new picture to show behind the game. */
    background?: File | null;
    /** Remove the current background picture. */
    remove_background?: boolean;
};

export type ActivityListItem = {
    id: number;
    title: string;
    game_type: GameTypeValue;
    game_type_label: string;
    language: string;
    items_count: number;
    clients_count: number;
    play_sessions_count: number;
};

export type GameCard = {
    word: Picture;
    pair: Picture | null;
    group_number: number | null;
    category: string | null;
};

export type GameSettings = {
    trial_count: number;
    shuffle: boolean;
    show_word_text: boolean;
    reward_every?: number;
    sticker_set?: string;
    field_size?: number;
    speak_word?: boolean;
    categories?: string[];
    target_level?: VoiceLevelValue;
    hold_seconds?: number;
};

export type GameActivity = {
    id?: number;
    title: string;
    game_type: GameTypeValue;
    language: string;
    settings: GameSettings;
    /** Picture shown behind the game; missing on activities saved offline before backgrounds existed. */
    background_url?: string | null;
};

/** Where a game's answers are saved: sent to the server, or kept on the device. */
export type GameRecorder = {
    /** Queue an answer; pass an existing UUID to correct that answer. Returns the UUID. */
    record: (answer: TrialAnswer, uuid?: string) => string;
    /** Resolves true once every answer is saved. */
    flush: () => Promise<boolean>;
    pendingCount: Readonly<Ref<number>>;
    hasConnectionProblem: Readonly<Ref<boolean>>;
    rejectionMessage: Readonly<Ref<string | null>>;
};

export type GameStickerBoard = {
    stickers: Readonly<Ref<string[]>>;
    add: (sticker: string) => void;
    /** Resolves true once the board is saved. */
    save: () => Promise<boolean>;
};

export type GameDirection = {
    id: number;
    text: string;
    item_ids: number[];
};

export type GamePrompt = {
    key: string;
    /** The word to find or say; the first card of a story. */
    target: Picture;
    /** The pictures shown to the child, in display order. */
    options: Picture[];
    /** Set for story sequence prompts: the story's cards in the correct order. */
    story?: { group_number: number; cards: Picture[] };
    /** Set for sort it prompts: the group the target belongs in, and every group offered. */
    sorting?: { category: string; categories: string[] };
    /** Set for follow directions prompts: the instruction and the pictures to touch, in order. */
    direction?: { id: number; text: string; steps: Picture[] };
    /** Set for balloon voice prompts: one try, numbered from 1. */
    balloon?: { tryNumber: number };
};

export type TrialMeta = {
    attempts?: number;
    chosen_item_id?: number;
    chosen_category?: string;
    touched_item_ids?: number[];
    held_ms?: number;
    average_level?: number;
};

export type TrialAnswer = {
    content_item_id: number | null;
    group_number?: number | null;
    activity_direction_id?: number | null;
    result: TrialResultValue;
    response_ms: number | null;
    meta?: TrialMeta | null;
};

export type SessionSummary = ResultTally & {
    total: number;
    accuracy: number;
    words: (Picture &
        ResultTally & { confusions: (Picture & { count: number })[] })[];
    stories: {
        group_number: number;
        result: TrialResultValue;
        attempts: number;
        cards: Picture[];
    }[];
    categories: (CategoryProgress & {
        confusions: { category: string; count: number }[];
    })[];
    directions: {
        text: string;
        steps: number;
        result: TrialResultValue;
        expected: Picture[];
        touched: Picture[];
    }[];
    steps: StepProgress[];
    tries: {
        result: TrialResultValue;
        held_ms: number;
        average_level: number | null;
    }[];
};

export type GoalStatusValue = 'active' | 'achieved' | 'discontinued';

export type GoalSession = {
    play_session_id: number;
    date: string;
    trials: number;
    correct: number;
    accuracy: number;
    met: boolean;
};

export type TherapyGoal = {
    id: number;
    description: string;
    status: GoalStatusValue;
    status_label: string;
    achieved_at: string | null;
    game_type: GameTypeValue | null;
    game_type_label: string | null;
    language: string | null;
    language_label: string | null;
    target_sound: string | null;
    word_position: string | null;
    word_position_label: string | null;
    target_accuracy: number;
    consecutive_sessions: number;
    min_trials_per_session: number;
    streak: number;
    recent_sessions: GoalSession[];
};

export type GoalOptions = {
    gameTypes: SelectOption[];
    languages: SelectOption[];
    wordPositions: SelectOption[];
};

export type SessionGoal = {
    id: number;
    description: string;
    status: GoalStatusValue;
    accuracy: number;
    met: boolean;
    target_accuracy: number;
    streak: number;
    consecutive_sessions: number;
};
