<script setup lang="ts">
import {
    CloudOff,
    Expand,
    HardDrive,
    LoaderCircle,
    Pause,
    Check,
    Volume2,
    VolumeX,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { useGameSounds } from '@/composables/useGameSounds';
import { games } from '@/games';
import { cn } from '@/lib/utils';
import type {
    GameActivity,
    GameCard,
    GameDirection,
    GamePrompt,
    GameRecorder,
    GameStickerBoard,
    Picture,
    TrialAnswer,
    TrialMeta,
    TrialResultValue,
} from '@/types';

/**
 * The full-screen game used for every session, online or offline. Where answers
 * and stickers are saved is up to the `recorder` and `stickerBoard` passed in.
 */
const props = defineProps<{
    activity: GameActivity;
    cards: GameCard[];
    directions: GameDirection[];
    stickerChoices: string[];
    /** Answers already recorded, when a paused session is resumed. */
    recordedTrials: number;
    recorder: GameRecorder;
    stickerBoard: GameStickerBoard;
    /** Called once every answer is saved, to end the session. */
    finish: () => Promise<void>;
    /** Whether answers are kept on this device instead of sent to the server. */
    savesOnDevice?: boolean;
}>();

const emit = defineEmits<{
    pause: [];
}>();

const celebrateEvery = 5;
const stickersOffered = 4;
const rewardEvery =
    props.stickerChoices.length > 0
        ? Number(props.activity.settings.reward_every ?? 0)
        : 0;
const celebrationMs = 1400;

const praise: Record<string, string[]> = {
    ms: ['Hebat!', 'Bagus!', 'Pandai!', 'Syabas!'],
    en: ['Great job!', 'Well done!', 'Super!', 'Amazing!'],
};
const celebrationEmoji = ['🌟', '🎉', '🏆', '🦄', '🚀', '🌈'];

const recorder = props.recorder;
const stickerBoard = props.stickerBoard;
const sounds = useGameSounds();

function shuffled<T>(items: T[]): T[] {
    const copy = [...items];

    for (let index = copy.length - 1; index > 0; index--) {
        const swap = Math.floor(Math.random() * (index + 1));
        [copy[index], copy[swap]] = [copy[swap], copy[index]];
    }

    return copy;
}

/** Shuffle story cards so they never start out already in order. */
function shuffledOutOfOrder(cards: Picture[]): Picture[] {
    if (cards.length < 2) {
        return [...cards];
    }

    let attempt = shuffled(cards);

    while (attempt.every((card, index) => card.id === cards[index].id)) {
        attempt = shuffled(cards);
    }

    return attempt;
}

/** Group story cards by story, keeping each story's correct order. */
function stories(): { group_number: number; cards: Picture[] }[] {
    const groups = new Map<number, Picture[]>();

    for (const card of props.cards) {
        if (card.group_number !== null) {
            groups.set(card.group_number, [
                ...(groups.get(card.group_number) ?? []),
                card.word,
            ]);
        }
    }

    return [...groups].map(([group_number, cards]) => ({
        group_number,
        cards,
    }));
}

/** Get the pictures offered for a word: its minimal pair, or distractors for Find it! */
function optionsFor(card: GameCard, target: Picture): Picture[] {
    if (card.pair) {
        return shuffled([card.word, card.pair]);
    }

    if (props.activity.game_type === 'find_it') {
        const distractors = shuffled(
            props.cards
                .map((other) => other.word)
                .filter((word) => word.id !== target.id),
        ).slice(0, (props.activity.settings.field_size ?? 4) - 1);

        return shuffled([target, ...distractors]);
    }

    return [card.word];
}

/** Stand-in target for balloon voice tries, which have no word. */
const balloonPicture: Picture = {
    id: 0,
    word: 'Balloon',
    emoji: '🎈',
    image_url: null,
};

/** Whether there is anything to play: balloon voice needs no words. */
const hasContent = computed(() => {
    switch (props.activity.game_type) {
        case 'balloon_voice':
            return true;
        case 'follow_directions':
            return props.cards.length > 0 && props.directions.length > 0;
        default:
            return props.cards.length > 0;
    }
});

/** Prompts generated so far; extended a round at a time as the child plays. */
const deck: GamePrompt[] = [];

function extendDeck(): void {
    if (props.activity.game_type === 'balloon_voice') {
        deck.push({
            key: `${deck.length}-balloon`,
            target: balloonPicture,
            options: [],
            balloon: { tryNumber: deck.length + 1 },
        });

        return;
    }

    if (props.activity.game_type === 'follow_directions') {
        const board = props.cards.map((card) => card.word);
        const pictures = new Map(board.map((picture) => [picture.id, picture]));
        const round = props.activity.settings.shuffle
            ? shuffled(props.directions)
            : props.directions;

        for (const direction of round) {
            const steps = direction.item_ids
                .map((id) => pictures.get(id))
                .filter((picture): picture is Picture => !!picture);

            deck.push({
                key: `${deck.length}-direction-${direction.id}`,
                target: steps[0] ?? board[0],
                options: props.activity.settings.shuffle
                    ? shuffled(board)
                    : board,
                direction: { id: direction.id, text: direction.text, steps },
            });
        }

        return;
    }

    if (props.activity.game_type === 'sort_it') {
        const round = props.activity.settings.shuffle
            ? shuffled(props.cards)
            : props.cards;

        for (const card of round) {
            deck.push({
                key: `${deck.length}-${card.word.id}`,
                target: card.word,
                options: [],
                sorting: {
                    category: card.category ?? '',
                    categories: props.activity.settings.categories ?? [],
                },
            });
        }

        return;
    }

    if (props.activity.game_type === 'story_sequence') {
        const round = props.activity.settings.shuffle
            ? shuffled(stories())
            : stories();

        for (const story of round) {
            deck.push({
                key: `${deck.length}-story-${story.group_number}`,
                target: story.cards[0],
                options: shuffledOutOfOrder(story.cards),
                story,
            });
        }

        return;
    }

    const round = props.activity.settings.shuffle
        ? shuffled(props.cards)
        : props.cards;

    for (const card of round) {
        const target = card.pair && Math.random() < 0.5 ? card.pair : card.word;

        deck.push({
            key: `${deck.length}-${target.id}`,
            target,
            options: optionsFor(card, target),
        });
    }
}

/** Extra props that only some games use. */
const gameProps = computed(() => {
    switch (props.activity.game_type) {
        case 'find_it':
        case 'follow_directions':
            return {
                language: props.activity.language,
                speakWord: props.activity.settings.speak_word ?? false,
            };
        case 'balloon_voice':
            return {
                language: props.activity.language,
                targetLevel: props.activity.settings.target_level ?? 'normal',
                holdSeconds: Number(props.activity.settings.hold_seconds ?? 3),
            };
        default:
            return {};
    }
});

const answeredCount = ref(props.recordedTrials);
const targetCount = ref(
    Math.max(
        props.activity.settings.trial_count,
        Math.ceil(props.recordedTrials / props.activity.settings.trial_count) *
            props.activity.settings.trial_count,
    ),
);

const isComplete = computed(() => answeredCount.value >= targetCount.value);

const currentPrompt = computed<GamePrompt | null>(() => {
    if (!hasContent.value) {
        return null;
    }

    while (deck.length <= answeredCount.value) {
        extendDeck();
    }

    return deck[answeredCount.value];
});

let promptShownAt = performance.now();

watch(currentPrompt, () => {
    promptShownAt = performance.now();
});

type LastAnswer = {
    uuid: string;
    prompt: GamePrompt;
    trial: TrialAnswer;
};

const lastAnswer = ref<LastAnswer | null>(null);
const celebration = ref<{ emoji: string; message: string } | null>(null);
/** The stickers the child can choose from right now, while the reward screen is open. */
const stickerOffer = ref<string[] | null>(null);
const isPaused = computed(
    () => celebration.value !== null || stickerOffer.value !== null,
);

function offerStickers(withSound: boolean): void {
    stickerOffer.value = shuffled(props.stickerChoices).slice(
        0,
        stickersOffered,
    );

    if (withSound) {
        sounds.playCelebration();
    }
}

function pickSticker(sticker: string): void {
    stickerBoard.add(sticker);
    stickerOffer.value = null;
}

/** Describe an answered prompt in the clinician's correction strip. */
function promptLabel(prompt: GamePrompt): string {
    if (prompt.story) {
        return `Story: ${prompt.target.word}…`;
    }

    if (prompt.direction) {
        return prompt.direction.text;
    }

    if (prompt.balloon) {
        return `Try ${prompt.balloon.tryNumber}`;
    }

    return prompt.target.word;
}

function celebrate(): void {
    const messages = praise[props.activity.language] ?? praise.en;

    celebration.value = {
        emoji: celebrationEmoji[
            Math.floor(Math.random() * celebrationEmoji.length)
        ],
        message: messages[Math.floor(Math.random() * messages.length)],
    };
    sounds.playCelebration();

    setTimeout(() => {
        celebration.value = null;
    }, celebrationMs);
}

function answer(
    result: TrialResultValue,
    meta: TrialMeta | null = null,
    answeredAt: number = performance.now(),
): void {
    const prompt = currentPrompt.value;

    if (!prompt || isComplete.value || isPaused.value) {
        return;
    }

    const trial: TrialAnswer = {
        content_item_id:
            prompt.story || prompt.direction || prompt.balloon
                ? null
                : prompt.target.id,
        group_number: prompt.story?.group_number ?? null,
        activity_direction_id: prompt.direction?.id ?? null,
        result,
        response_ms: Math.max(0, Math.round(answeredAt - promptShownAt)),
        meta,
    };
    const uuid = recorder.record(trial);

    lastAnswer.value = { uuid, prompt, trial };
    answeredCount.value++;

    const earnsSticker =
        rewardEvery > 0 && answeredCount.value % rewardEvery === 0;

    if (earnsSticker) {
        offerStickers(!isComplete.value);
    }

    if (isComplete.value) {
        sounds.playFinish();
    } else if (
        rewardEvery === 0 &&
        answeredCount.value % celebrateEvery === 0
    ) {
        celebrate();
    }
}

function correctLastAnswer(result: TrialResultValue): void {
    const last = lastAnswer.value;

    if (!last || last.trial.result === result) {
        return;
    }

    last.trial = { ...last.trial, result };
    recorder.record(last.trial, last.uuid);
}

function keepGoing(): void {
    targetCount.value += props.activity.settings.trial_count;
}

const isEnding = ref(false);
const endError = ref<string | null>(null);

async function endSession(): Promise<void> {
    isEnding.value = true;
    endError.value = null;

    await stickerBoard.save();

    if (!(await recorder.flush())) {
        endError.value =
            recorder.rejectionMessage.value ??
            `${recorder.pendingCount.value} answers are not saved yet. Check the connection and try again.`;
        isEnding.value = false;

        return;
    }

    try {
        await props.finish();
    } finally {
        isEnding.value = false;
    }
}

function enterFullscreen(): void {
    void document.documentElement.requestFullscreen?.().catch(() => {});
}

const progressPercent = computed(() =>
    Math.min(100, (answeredCount.value / targetCount.value) * 100),
);

const correctionButtons = [
    { result: 'correct', label: '✓', title: 'Correct' },
    { result: 'prompted', label: '🤝', title: 'Correct with prompt' },
    { result: 'incorrect', label: '✗', title: 'Incorrect' },
] as const;
</script>

<template>
    <div class="fixed inset-0 z-50 flex flex-col bg-background">
        <header
            class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b px-3 py-2"
        >
            <div class="flex min-w-40 flex-1 items-center gap-3">
                <span class="text-sm font-semibold tabular-nums">
                    {{ Math.min(answeredCount, targetCount) }} /
                    {{ targetCount }}
                </span>
                <div
                    class="h-2 max-w-64 flex-1 overflow-hidden rounded-full bg-muted"
                    role="progressbar"
                    :aria-valuenow="Math.round(progressPercent)"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-label="Session progress"
                >
                    <div
                        class="h-full rounded-full bg-primary transition-all"
                        :style="{ width: `${progressPercent}%` }"
                    />
                </div>
                <span
                    class="hidden truncate text-sm text-muted-foreground md:inline"
                >
                    {{ activity.title }}
                </span>
            </div>

            <div
                v-if="lastAnswer"
                class="flex items-center gap-1 rounded-md border px-2 py-1 text-xs"
            >
                <span class="text-muted-foreground">Last:</span>
                <span class="max-w-48 truncate font-medium">{{
                    promptLabel(lastAnswer.prompt)
                }}</span>
                <button
                    v-for="button in correctionButtons"
                    :key="button.result"
                    type="button"
                    :title="`Change to ${button.title}`"
                    :aria-label="`Change last answer to ${button.title}`"
                    :aria-pressed="lastAnswer.trial.result === button.result"
                    :class="
                        cn(
                            'rounded px-1.5 py-0.5',
                            lastAnswer.trial.result === button.result
                                ? 'bg-primary text-primary-foreground'
                                : 'hover:bg-muted',
                        )
                    "
                    @click="correctLastAnswer(button.result)"
                >
                    {{ button.label }}
                </button>
            </div>

            <div
                v-if="rewardEvery > 0"
                class="flex items-center gap-1 text-sm"
                :title="`${stickerBoard.stickers.value.length} stickers earned`"
            >
                <span>{{ stickerBoard.stickers.value.at(-1) ?? '⭐' }}</span>
                <span class="font-semibold tabular-nums">
                    {{ stickerBoard.stickers.value.length }}
                </span>
            </div>

            <div class="flex items-center gap-1 text-xs text-muted-foreground">
                <template v-if="savesOnDevice">
                    <HardDrive class="size-4" />
                    Saved on this device
                </template>
                <template v-else-if="recorder.hasConnectionProblem.value">
                    <CloudOff class="size-4 text-amber-600" />
                    Offline · {{ recorder.pendingCount.value }} waiting
                </template>
                <template v-else-if="recorder.pendingCount.value > 0">
                    <LoaderCircle class="size-4 animate-spin" />
                    Saving
                </template>
                <template v-else>
                    <Check class="size-4 text-emerald-600" />
                    Saved
                </template>
            </div>

            <div class="flex items-center gap-1">
                <Button
                    variant="ghost"
                    size="icon"
                    :aria-label="
                        sounds.isMuted.value ? 'Turn sound on' : 'Mute sound'
                    "
                    :title="
                        sounds.isMuted.value ? 'Turn sound on' : 'Mute sound'
                    "
                    :aria-pressed="sounds.isMuted.value"
                    @click="sounds.toggleMuted"
                >
                    <VolumeX v-if="sounds.isMuted.value" />
                    <Volume2 v-else />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label="Full screen"
                    @click="enterFullscreen"
                >
                    <Expand />
                </Button>
                <Button variant="ghost" size="sm" @click="emit('pause')">
                    <Pause />
                    Pause
                </Button>
                <Button size="sm" :disabled="isEnding" @click="endSession">
                    <LoaderCircle v-if="isEnding" class="animate-spin" />
                    End session
                </Button>
            </div>
        </header>

        <p
            v-if="endError || recorder.rejectionMessage.value"
            class="bg-destructive/10 px-4 py-2 text-center text-sm text-destructive"
        >
            {{ endError ?? recorder.rejectionMessage.value }}
        </p>

        <main class="relative isolate flex flex-1 flex-col overflow-hidden">
            <template v-if="activity.background_url">
                <div
                    class="pointer-events-none absolute inset-0 -z-10 bg-cover bg-center"
                    :style="{
                        backgroundImage: `url(${JSON.stringify(activity.background_url)})`,
                    }"
                    aria-hidden="true"
                />
                <div
                    class="pointer-events-none absolute inset-0 -z-10 bg-background/40"
                    aria-hidden="true"
                />
            </template>

            <div
                v-if="!hasContent"
                class="flex flex-1 items-center justify-center p-6 text-center text-muted-foreground"
            >
                <template v-if="activity.game_type === 'follow_directions'">
                    This activity needs pictures on the board and at least one
                    direction. Edit the activity to add them.
                </template>
                <template v-else>
                    This activity has no words yet. Edit the activity to add
                    some.
                </template>
            </div>

            <div
                v-else-if="isComplete"
                class="flex flex-1 flex-col items-center justify-center gap-6 p-6 text-center"
            >
                <div class="animate-bounce text-8xl">🏆</div>
                <p class="text-3xl font-bold">
                    {{
                        activity.language === 'ms'
                            ? 'Syabas, semua selesai!'
                            : 'All done, great work!'
                    }}
                </p>
                <div
                    v-if="stickerBoard.stickers.value.length"
                    class="max-w-xl space-y-2"
                >
                    <p class="text-xl font-semibold">
                        {{ stickerBoard.stickers.value.length }}
                        {{
                            activity.language === 'ms'
                                ? 'pelekat dikumpul!'
                                : stickerBoard.stickers.value.length === 1
                                  ? 'sticker earned!'
                                  : 'stickers earned!'
                        }}
                    </p>
                    <p class="flex flex-wrap justify-center gap-1 text-5xl">
                        <span
                            v-for="(sticker, index) in stickerBoard.stickers
                                .value"
                            :key="index"
                        >
                            {{ sticker }}
                        </span>
                    </p>
                </div>
                <div class="flex flex-wrap justify-center gap-3">
                    <Button variant="outline" size="lg" @click="keepGoing">
                        Keep going
                    </Button>
                    <Button size="lg" :disabled="isEnding" @click="endSession">
                        <LoaderCircle v-if="isEnding" class="animate-spin" />
                        End session and see results
                    </Button>
                </div>
            </div>

            <component
                :is="games[activity.game_type]"
                v-else-if="currentPrompt"
                :prompt="currentPrompt"
                :show-word-text="activity.settings.show_word_text"
                v-bind="gameProps"
                :disabled="isPaused"
                @answer="answer"
            />

            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="scale-50 opacity-0"
                leave-active-class="transition duration-200 ease-in"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="celebration"
                    class="absolute inset-0 flex flex-col items-center justify-center gap-4 bg-background/85"
                    aria-live="polite"
                >
                    <span class="animate-bounce text-[9rem] leading-none">
                        {{ celebration.emoji }}
                    </span>
                    <span class="text-4xl font-bold">{{
                        celebration.message
                    }}</span>
                </div>
            </Transition>

            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="scale-75 opacity-0"
                leave-active-class="transition duration-200 ease-in"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="stickerOffer"
                    class="absolute inset-0 flex flex-col items-center justify-center gap-6 bg-background/90 p-6 text-center"
                    role="dialog"
                    aria-label="Choose a sticker"
                >
                    <p class="text-4xl font-bold">
                        {{
                            activity.language === 'ms'
                                ? 'Pilih satu pelekat!'
                                : 'Pick a sticker!'
                        }}
                    </p>
                    <div class="flex flex-wrap justify-center gap-4">
                        <button
                            v-for="sticker in stickerOffer"
                            :key="sticker"
                            type="button"
                            class="flex size-28 items-center justify-center rounded-3xl border-4 border-border bg-background text-7xl shadow-sm transition-transform hover:scale-110 hover:border-primary sm:size-36 sm:text-8xl"
                            :aria-label="`Sticker ${sticker}`"
                            @click="pickSticker(sticker)"
                        >
                            {{ sticker }}
                        </button>
                    </div>
                    <p
                        v-if="stickerBoard.stickers.value.length"
                        class="flex max-w-xl flex-wrap justify-center gap-1 text-3xl"
                        aria-label="Sticker board"
                    >
                        <span
                            v-for="(sticker, index) in stickerBoard.stickers
                                .value"
                            :key="index"
                        >
                            {{ sticker }}
                        </span>
                    </p>
                </div>
            </Transition>
        </main>
    </div>
</template>
