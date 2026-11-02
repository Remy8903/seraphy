<script setup lang="ts">
import { Volume2 } from '@lucide/vue';
import { onMounted, ref, watch } from 'vue';
import PictureCard from '@/components/therapy/PictureCard.vue';
import { useGameSounds } from '@/composables/useGameSounds';
import { useSoundClip } from '@/composables/useSoundClip';
import { cn } from '@/lib/utils';
import type { GamePrompt, TrialMeta, TrialResultValue } from '@/types';

const props = defineProps<{
    prompt: GamePrompt;
    showWordText: boolean;
    disabled: boolean;
    language: string;
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
const clip = useSoundClip();

function playSound(): void {
    if (props.prompt.target.sound_url) {
        void clip.play(props.prompt.target.sound_url);
    }
}

function startPrompt(): void {
    tappedId.value = null;
    clip.preload(props.prompt.options.map((option) => option.sound_url));
    playSound();
}

onMounted(startPrompt);
watch(() => props.prompt.key, startPrompt);

function tap(pictureId: number): void {
    if (props.disabled || tappedId.value !== null) {
        return;
    }

    clip.stop();
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
        return 'border-border bg-background hover:border-primary';
    }

    if (pictureId === props.prompt.target.id) {
        return 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40';
    }

    return pictureId === tappedId.value
        ? 'border-rose-500 bg-rose-50 dark:bg-rose-950/40 animate-[shake_0.4s_ease-in-out]'
        : 'border-border bg-background opacity-60';
}
</script>

<template>
    <div class="flex flex-1 flex-col select-none">
        <div class="flex flex-col items-center gap-3 px-6 pt-6">
            <button
                type="button"
                :disabled="disabled || !prompt.target.sound_url"
                :class="
                    cn(
                        'relative flex size-28 items-center justify-center rounded-full bg-linear-to-br from-brand-violet via-brand-indigo to-brand-blue text-white shadow-lg transition-transform hover:scale-105 active:scale-95 disabled:opacity-50 sm:size-32',
                        clip.isPlaying.value && 'scale-105',
                    )
                "
                :aria-label="
                    language === 'ms'
                        ? 'Main bunyi sekali lagi'
                        : 'Play the sound again'
                "
                @click="playSound"
            >
                <span
                    v-if="clip.isPlaying.value"
                    class="absolute inset-0 animate-ping rounded-full bg-brand-indigo/40"
                    aria-hidden="true"
                />
                <Volume2 class="relative size-14 sm:size-16" />
            </button>
            <p class="text-2xl font-bold sm:text-3xl">
                {{
                    language === 'ms'
                        ? 'Bunyi siapa ini?'
                        : 'Who makes this sound?'
                }}
            </p>
        </div>

        <div
            class="mx-auto grid w-full max-w-5xl flex-1 content-center gap-4 p-6"
            :class="
                prompt.options.length <= 2
                    ? 'grid-cols-2'
                    : prompt.options.length === 3
                      ? 'grid-cols-3'
                      : 'grid-cols-2 sm:grid-cols-4'
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
            class="border-t bg-muted/60 p-4 text-center text-xs text-muted-foreground"
        >
            <template v-if="!prompt.target.sound_url">
                This animal has no sound. Add one in the word library, or make
                the sound yourself.
            </template>
            <template v-else-if="clip.hasFailed.value">
                The sound could not play. Check the volume, tap 🔊 to try again,
                or make the sound yourself.
            </template>
            <template v-else>
                Clinician: the sound plays by itself. Tap 🔊 to hear it again.
            </template>
            <span class="ml-1 font-medium text-foreground">
                Answer: {{ prompt.target.word }}
            </span>
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
