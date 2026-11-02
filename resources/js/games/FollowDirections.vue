<script setup lang="ts">
import { RotateCcw, SkipForward, Volume2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import PictureCard from '@/components/therapy/PictureCard.vue';
import { Button } from '@/components/ui/button';
import { useGameSounds } from '@/composables/useGameSounds';
import { useSpeech } from '@/composables/useSpeech';
import type { GamePrompt, TrialMeta, TrialResultValue } from '@/types';

const props = defineProps<{
    prompt: GamePrompt;
    showWordText: boolean;
    disabled: boolean;
    language: string;
    speakWord: boolean;
}>();

const emit = defineEmits<{
    answer: [
        result: TrialResultValue,
        meta: TrialMeta | null,
        answeredAt: number,
    ];
}>();

const feedbackDelayMs = 1200;
const { playAnswer } = useGameSounds();
const speech = useSpeech(() => props.language);

const steps = computed(() => props.prompt.direction?.steps ?? []);
const touched = ref<number[]>([]);
/** The pictures touched on the first full attempt, sent with the answer. */
const firstAttempt = ref<number[] | null>(null);
/** Whether the clinician has repeated the direction; success then scores as prompted. */
const wasRepeated = ref(false);
const status = ref<'playing' | 'wrong' | 'done'>('playing');
const isLocked = computed(() => props.disabled || status.value !== 'playing');
const canSpeak = computed(() => props.speakWord && speech.hasVoice.value);

function readAloud(): void {
    speech.speak(props.prompt.direction?.text ?? '');
}

watch(
    () => props.prompt.key,
    () => {
        touched.value = [];
        firstAttempt.value = null;
        wasRepeated.value = false;
        status.value = 'playing';

        if (canSpeak.value) {
            readAloud();
        }
    },
);

// Voices can load after mount, so read the first direction once one is ready.
watch(
    canSpeak,
    (speakable) => {
        if (speakable && touched.value.length === 0 && !wasRepeated.value) {
            readAloud();
        }
    },
    { immediate: true },
);

function touch(pictureId: number): void {
    if (isLocked.value || touched.value.includes(pictureId)) {
        return;
    }

    touched.value = [...touched.value, pictureId];

    if (touched.value.length < steps.value.length) {
        return;
    }

    const answeredAt = performance.now();
    const isCorrect = touched.value.every(
        (id, index) => id === steps.value[index]?.id,
    );
    firstAttempt.value ??= touched.value;

    if (!isCorrect) {
        status.value = 'wrong';
        playAnswer('incorrect');

        return;
    }

    const result: TrialResultValue = wasRepeated.value ? 'prompted' : 'correct';
    status.value = 'done';
    playAnswer(result);
    setTimeout(
        () =>
            emit(
                'answer',
                result,
                { touched_item_ids: firstAttempt.value ?? touched.value },
                answeredAt,
            ),
        feedbackDelayMs,
    );
}

/** Repeat the direction; a success after this counts as prompted. */
function repeat(): void {
    if (props.disabled || status.value === 'done') {
        return;
    }

    wasRepeated.value = true;
    touched.value = [];
    status.value = 'playing';

    if (canSpeak.value) {
        readAloud();
    }
}

function moveOn(): void {
    if (props.disabled || status.value === 'done') {
        return;
    }

    status.value = 'done';
    emit(
        'answer',
        'incorrect',
        { touched_item_ids: firstAttempt.value ?? touched.value },
        performance.now(),
    );
}

function pictureClasses(pictureId: number): string {
    const position = touched.value.indexOf(pictureId);

    if (position < 0) {
        return 'border-border hover:border-primary hover:bg-primary/5';
    }

    if (status.value === 'playing') {
        return 'border-primary bg-primary/10';
    }

    return steps.value[position]?.id === pictureId
        ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40'
        : 'border-rose-500 bg-rose-50 dark:bg-rose-950/40 animate-[shake_0.4s_ease-in-out]';
}
</script>

<template>
    <div class="flex flex-1 flex-col select-none">
        <div
            class="mx-auto grid w-full max-w-6xl flex-1 content-center gap-4 p-6"
            :class="
                prompt.options.length <= 4
                    ? 'grid-cols-2 sm:grid-cols-4'
                    : 'grid-cols-3 sm:grid-cols-4'
            "
        >
            <button
                v-for="option in prompt.options"
                :key="`${prompt.key}-${option.id}`"
                type="button"
                :disabled="isLocked"
                :class="[
                    'relative flex items-center justify-center rounded-3xl border-4 p-3 transition-all',
                    pictureClasses(option.id),
                ]"
                :aria-label="option.word"
                @click="touch(option.id)"
            >
                <span
                    v-if="touched.includes(option.id)"
                    class="absolute top-2 left-2 flex size-9 items-center justify-center rounded-full bg-primary text-lg font-bold text-primary-foreground"
                >
                    {{ touched.indexOf(option.id) + 1 }}
                </span>
                <PictureCard
                    :picture="option"
                    size="lg"
                    :show-word="showWordText"
                />
            </button>
        </div>

        <div
            class="flex flex-wrap items-center justify-center gap-3 border-t bg-muted/60 p-4 text-center"
        >
            <div>
                <p class="text-xs text-muted-foreground">
                    <template v-if="status === 'wrong'">
                        Not quite. Repeat the direction for another try, or move
                        on.
                    </template>
                    <template v-else-if="canSpeak">
                        The direction is read aloud. Repeating it counts as a
                        prompt.
                    </template>
                    <template v-else>
                        Clinician: read the direction once. Repeating it counts
                        as a prompt.
                    </template>
                </p>
                <p class="text-2xl font-semibold">
                    “{{ prompt.direction?.text }}”
                    <span class="text-sm font-normal text-muted-foreground">
                        · {{ steps.length }}
                        {{ steps.length === 1 ? 'step' : 'steps' }}
                    </span>
                </p>
            </div>
            <div class="flex gap-2">
                <Button
                    variant="outline"
                    :disabled="disabled || status === 'done'"
                    @click="repeat"
                >
                    <Volume2 v-if="canSpeak" />
                    <RotateCcw v-else />
                    {{ status === 'wrong' ? 'Repeat and try again' : 'Repeat' }}
                </Button>
                <Button
                    variant="outline"
                    :disabled="disabled || status === 'done'"
                    @click="moveOn"
                >
                    <SkipForward />
                    Move on
                </Button>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes shake {
    0%,
    100% {
        transform: translateX(0);
    }
    25% {
        transform: translateX(-8px);
    }
    75% {
        transform: translateX(8px);
    }
}
</style>
