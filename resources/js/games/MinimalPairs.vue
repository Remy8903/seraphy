<script setup lang="ts">
import { ref, watch } from 'vue';
import PictureCard from '@/components/therapy/PictureCard.vue';
import { useGameSounds } from '@/composables/useGameSounds';
import type { GamePrompt, TrialMeta, TrialResultValue } from '@/types';

const props = defineProps<{
    prompt: GamePrompt;
    showWordText: boolean;
    disabled: boolean;
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

watch(
    () => props.prompt.key,
    () => {
        tappedId.value = null;
    },
);

function tap(pictureId: number): void {
    if (props.disabled || tappedId.value !== null) {
        return;
    }

    tappedId.value = pictureId;
    const answeredAt = performance.now();
    const result: TrialResultValue =
        pictureId === props.prompt.target.id ? 'correct' : 'incorrect';

    playAnswer(result);
    setTimeout(() => emit('answer', result, null, answeredAt), feedbackDelayMs);
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
            class="flex flex-1 flex-wrap items-center justify-center gap-6 p-6 sm:gap-12"
        >
            <button
                v-for="option in prompt.options"
                :key="`${prompt.key}-${option.id}`"
                type="button"
                :disabled="disabled"
                :class="[
                    'rounded-3xl border-4 p-4 transition-all sm:p-6',
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

        <div class="border-t bg-muted/60 p-4 text-center">
            <p class="text-xs text-muted-foreground">
                Clinician: say the word, then let the child tap a picture
            </p>
            <p class="text-2xl font-semibold">
                Say: “{{ prompt.target.word }}”
            </p>
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
