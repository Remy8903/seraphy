<script setup lang="ts">
import { Volume2 } from '@lucide/vue';
import { ref, watch } from 'vue';
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

const feedbackDelayMs = 900;
const tappedId = ref<number | null>(null);
const { playAnswer } = useGameSounds();
const speech = useSpeech(() => props.language);

function sayWord(): void {
    speech.speak(props.prompt.target.word);
}

watch(
    () => props.prompt.key,
    () => {
        tappedId.value = null;

        if (props.speakWord) {
            sayWord();
        }
    },
);

// Voices can load after mount, so speak the first word once one is ready.
watch(
    speech.hasVoice,
    (hasVoice) => {
        if (hasVoice && props.speakWord && tappedId.value === null) {
            sayWord();
        }
    },
    { immediate: true },
);

function tap(pictureId: number): void {
    if (props.disabled || tappedId.value !== null) {
        return;
    }

    tappedId.value = pictureId;
    const answeredAt = performance.now();
    const isCorrect = pictureId === props.prompt.target.id;
    const result: TrialResultValue = isCorrect ? 'correct' : 'incorrect';

    playAnswer(result);
    setTimeout(
        () =>
            emit(
                'answer',
                result,
                isCorrect ? null : { chosen_item_id: pictureId },
                answeredAt,
            ),
        feedbackDelayMs,
    );
}

function optionClasses(pictureId: number): string {
    if (tappedId.value === null) {
        return 'border-border hover:border-primary hover:bg-primary/5';
    }

    if (pictureId === props.prompt.target.id) {
        return 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40';
    }

    return pictureId === tappedId.value
        ? 'border-rose-500 bg-rose-50 dark:bg-rose-950/40 animate-[shake_0.4s_ease-in-out]'
        : 'border-border opacity-60';
}
</script>

<template>
    <div class="flex flex-1 flex-col">
        <div
            class="mx-auto grid w-full max-w-5xl flex-1 content-center gap-4 p-6"
            :class="
                prompt.options.length <= 3
                    ? 'grid-cols-3'
                    : prompt.options.length === 4
                      ? 'grid-cols-2 sm:grid-cols-4'
                      : 'grid-cols-3'
            "
        >
            <button
                v-for="option in prompt.options"
                :key="`${prompt.key}-${option.id}`"
                type="button"
                :disabled="disabled"
                :class="[
                    'flex items-center justify-center rounded-3xl border-4 p-3 transition-all sm:p-5',
                    optionClasses(option.id),
                ]"
                :aria-label="option.word"
                @click="tap(option.id)"
            >
                <PictureCard
                    :picture="option"
                    size="lg"
                    :show-word="showWordText"
                />
            </button>
        </div>

        <div
            class="flex items-center justify-center gap-3 border-t bg-muted/60 p-4 text-center"
        >
            <div>
                <p class="text-xs text-muted-foreground">
                    <template v-if="speakWord && speech.hasVoice.value">
                        The word is spoken aloud. Tap 🔊 to repeat it.
                    </template>
                    <template v-else>
                        Clinician: say the word, then let the child find it
                    </template>
                </p>
                <p class="text-2xl font-semibold">
                    Say: “{{ prompt.target.word }}”
                </p>
            </div>
            <Button
                v-if="speakWord && speech.hasVoice.value"
                variant="outline"
                size="icon"
                aria-label="Say the word again"
                @click="sayWord"
            >
                <Volume2 />
            </Button>
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
