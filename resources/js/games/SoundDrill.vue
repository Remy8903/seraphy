<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue';
import PictureCard from '@/components/therapy/PictureCard.vue';
import { useGameSounds } from '@/composables/useGameSounds';
import type { GamePrompt, TrialResultValue } from '@/types';

const props = defineProps<{
    prompt: GamePrompt;
    showWordText: boolean;
    disabled: boolean;
}>();

const emit = defineEmits<{
    answer: [result: TrialResultValue];
}>();

const { playAnswer } = useGameSounds();

const scoreButtons = [
    {
        result: 'correct',
        key: '1',
        label: 'Correct',
        symbol: '✓',
        classes: 'bg-emerald-600 hover:bg-emerald-700 text-white',
    },
    {
        result: 'prompted',
        key: '2',
        label: 'With prompt',
        symbol: '🤝',
        classes: 'bg-amber-500 hover:bg-amber-600 text-white',
    },
    {
        result: 'incorrect',
        key: '3',
        label: 'Incorrect',
        symbol: '✗',
        classes: 'bg-rose-600 hover:bg-rose-700 text-white',
    },
] as const;

function score(result: TrialResultValue): void {
    if (!props.disabled) {
        playAnswer(result);
        emit('answer', result);
    }
}

function scoreFromKeyboard(event: KeyboardEvent): void {
    if (event.target instanceof HTMLInputElement || event.repeat) {
        return;
    }

    const button = scoreButtons.find(
        (candidate) => candidate.key === event.key,
    );

    if (button) {
        score(button.result);
    }
}

onMounted(() => window.addEventListener('keydown', scoreFromKeyboard));
onBeforeUnmount(() => window.removeEventListener('keydown', scoreFromKeyboard));
</script>

<template>
    <div class="flex flex-1 flex-col">
        <div class="flex flex-1 items-center justify-center p-6">
            <Transition
                mode="out-in"
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="scale-75 opacity-0"
                leave-active-class="transition duration-150 ease-in"
                leave-to-class="scale-90 opacity-0"
            >
                <PictureCard
                    :key="prompt.key"
                    :picture="prompt.target"
                    size="xl"
                    :show-word="showWordText"
                />
            </Transition>
        </div>

        <div class="border-t bg-muted/60 p-3">
            <p class="mb-2 text-center text-xs text-muted-foreground">
                Clinician: score the attempt at
                <strong class="text-foreground">{{
                    prompt.target.word
                }}</strong>
                · keys 1, 2, 3
            </p>
            <div class="mx-auto grid max-w-xl grid-cols-3 gap-3">
                <button
                    v-for="button in scoreButtons"
                    :key="button.result"
                    type="button"
                    :disabled="disabled"
                    :class="[
                        'flex flex-col items-center gap-0.5 rounded-xl px-3 py-3 font-medium transition-colors disabled:opacity-50',
                        button.classes,
                    ]"
                    @click="score(button.result)"
                >
                    <span class="text-2xl leading-none">{{
                        button.symbol
                    }}</span>
                    <span class="text-sm">{{ button.label }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
