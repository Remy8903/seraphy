<script setup lang="ts">
import { Mic, MicOff, RotateCcw, ShieldCheck, SkipForward } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { useGameSounds } from '@/composables/useGameSounds';
import {
    average,
    useVoiceLevel,
    voicedThreshold,
} from '@/composables/useVoiceLevel';
import type {
    GamePrompt,
    TrialMeta,
    TrialResultValue,
    VoiceLevelValue,
} from '@/types';

const props = defineProps<{
    prompt: GamePrompt;
    disabled: boolean;
    language: string;
    targetLevel: VoiceLevelValue;
    holdSeconds: number;
}>();

const emit = defineEmits<{
    answer: [
        result: TrialResultValue,
        meta: TrialMeta | null,
        answeredAt: number,
    ];
}>();

/** Target ranges relative to the calibrated normal voice (1 = normal). */
const targetRanges: Record<VoiceLevelValue, [number, number]> = {
    quiet: [0.2, 0.7],
    normal: [0.7, 1.5],
    loud: [1.5, Number.POSITIVE_INFINITY],
};
const meterMax = 3;
const calibrationStepMs = 3000;
const smoothing = 0.25;
const feedbackDelayMs = 1400;

const microphone = useVoiceLevel();
const { playAnswer } = useGameSounds();

type Phase = 'setup' | 'quiet' | 'voice' | 'playing' | 'done';

const phase = ref<Phase>('setup');
const calibrationError = ref<string | null>(null);
const level = ref(0);
const height = ref(0);
const inRange = ref(false);

let heldMs = 0;
let voicedLevels: number[] = [];
let lastFrameAt: number | null = null;

const range = computed(() => targetRanges[props.targetLevel]);
const holdMs = computed(() => props.holdSeconds * 1000);
const isMalay = computed(() => props.language === 'ms');

function resetTry(): void {
    height.value = 0;
    heldMs = 0;
    voicedLevels = [];
    lastFrameAt = null;
    inRange.value = false;
}

function finishTry(result: TrialResultValue): void {
    phase.value = 'done';
    playAnswer(result);

    const meta: TrialMeta = {
        held_ms: Math.round(heldMs),
        ...(voicedLevels.length
            ? { average_level: Math.round(average(voicedLevels) * 100) / 100 }
            : {}),
    };
    const answeredAt = performance.now();

    setTimeout(
        () => emit('answer', result, meta, answeredAt),
        result === 'correct' ? feedbackDelayMs : 0,
    );
}

/** Move the balloon: it rises while the voice is in range and slowly sinks when it is not. */
function onLevel(raw: number, now: number): void {
    level.value =
        level.value + (microphone.relative(raw) - level.value) * smoothing;

    if (phase.value !== 'playing' || props.disabled) {
        lastFrameAt = null;

        return;
    }

    const elapsed = lastFrameAt === null ? 0 : now - lastFrameAt;
    lastFrameAt = now;

    if (level.value > voicedThreshold) {
        voicedLevels.push(level.value);
    }

    inRange.value =
        level.value >= range.value[0] && level.value <= range.value[1];

    if (inRange.value) {
        heldMs += elapsed;
        height.value = Math.min(1, height.value + elapsed / holdMs.value);
    } else {
        height.value = Math.max(0, height.value - elapsed / (holdMs.value * 2));
    }

    if (height.value >= 1) {
        finishTry('correct');
    }
}

const unsubscribe = microphone.onLevel(onLevel);
onBeforeUnmount(unsubscribe);

async function calibrate(): Promise<void> {
    calibrationError.value = null;
    phase.value = 'quiet';
    const quietLevels = await microphone.collect(calibrationStepMs);

    phase.value = 'voice';
    const voiceLevels = await microphone.collect(calibrationStepMs);

    const quiet = average(quietLevels);
    const loudestHalf = [...voiceLevels]
        .sort((first, second) => second - first)
        .slice(0, Math.max(1, Math.floor(voiceLevels.length / 2)));
    const normal = average(loudestHalf);

    if (normal < quiet * 2 || normal - quiet < 0.002) {
        microphone.calibration.value = null;
        calibrationError.value =
            'We could not hear a voice clearly. Move closer to the device, or find a quieter spot, and try again.';
        phase.value = 'setup';

        return;
    }

    microphone.calibration.value = { quiet, normal };
    resetTry();
    phase.value = 'playing';
}

async function turnOnMicrophone(): Promise<void> {
    await microphone.start();

    if (microphone.state.value !== 'on') {
        return;
    }

    if (microphone.calibration.value) {
        resetTry();
        phase.value = 'playing';
    } else {
        await calibrate();
    }
}

watch(
    () => props.prompt.key,
    () => {
        if (phase.value === 'done') {
            resetTry();
            phase.value = 'playing';
        }
    },
);

function notThisTime(): void {
    if (phase.value === 'playing' && !props.disabled) {
        finishTry('incorrect');
    }
}

/** Position of a relative level on the meter, from 0 (bottom) to 100 (top). */
function meterPercent(value: number): number {
    return Math.min(100, (Math.min(value, meterMax) / meterMax) * 100);
}

const microphoneMessage = computed(() => {
    switch (microphone.state.value) {
        case 'denied':
            return 'Microphone access is blocked. Click the icon at the left of the address bar, set Microphone to Allow, then reload the page.';
        case 'insecure':
            return 'The microphone only works on a secure (https) address. Open Seraphy using https and try again.';
        case 'unavailable':
            return 'No microphone was found. Connect or enable a microphone and try again.';
        default:
            return null;
    }
});
</script>

<template>
    <div class="flex flex-1 flex-col select-none">
        <div
            v-if="phase === 'setup'"
            class="mx-auto flex max-w-lg flex-1 flex-col items-center justify-center gap-5 p-6 text-center"
        >
            <span class="text-8xl leading-none">🎈</span>
            <div
                class="flex items-start gap-3 rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-left text-sm text-emerald-950 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100"
            >
                <ShieldCheck class="mt-0.5 size-5 shrink-0" />
                <p>
                    This game listens to the child&rsquo;s voice to move the
                    balloon. Sound is measured
                    <strong>on this device only</strong>, and it is never
                    recorded, stored or uploaded.
                </p>
            </div>
            <p class="text-sm text-muted-foreground">
                First we set the scale for this room: 3 seconds of quiet, then 3
                seconds of the child&rsquo;s normal voice.
            </p>
            <p
                v-if="microphoneMessage"
                class="flex items-start gap-2 rounded-md bg-destructive/10 p-3 text-left text-sm text-destructive"
                role="alert"
            >
                <MicOff class="mt-0.5 size-4 shrink-0" />
                {{ microphoneMessage }}
            </p>
            <p
                v-if="calibrationError"
                class="rounded-md bg-amber-100 p-3 text-sm text-amber-900 dark:bg-amber-950/40 dark:text-amber-100"
                role="alert"
            >
                {{ calibrationError }}
            </p>
            <Button
                size="lg"
                :disabled="microphone.state.value === 'starting'"
                @click="turnOnMicrophone"
            >
                <Mic />
                {{
                    microphone.state.value === 'on'
                        ? 'Calibrate again'
                        : 'Turn on microphone'
                }}
            </Button>
        </div>

        <div
            v-else-if="phase === 'quiet' || phase === 'voice'"
            class="flex flex-1 flex-col items-center justify-center gap-4 p-6 text-center"
            aria-live="polite"
        >
            <span class="text-8xl leading-none">
                {{ phase === 'quiet' ? '🤫' : '🗣️' }}
            </span>
            <p class="text-3xl font-bold">
                <template v-if="phase === 'quiet'">
                    {{ isMalay ? 'Senyap sekejap…' : 'Stay quiet…' }}
                </template>
                <template v-else>
                    {{
                        isMalay
                            ? 'Sebut “aaaa” dengan suara biasa'
                            : 'Say “aaaa” in your normal voice'
                    }}
                </template>
            </p>
            <p class="text-sm text-muted-foreground">
                Setting the scale ·
                {{ phase === 'quiet' ? 'step 1 of 2' : 'step 2 of 2' }}
            </p>
        </div>

        <div v-else class="flex flex-1 gap-4 overflow-hidden p-4 sm:p-6">
            <div
                class="relative w-8 shrink-0 overflow-hidden rounded-full bg-muted"
                role="meter"
                aria-label="Voice level"
                :aria-valuenow="Math.round(level * 100) / 100"
                aria-valuemin="0"
                :aria-valuemax="meterMax"
            >
                <div
                    class="absolute inset-x-0 bg-emerald-400/40"
                    :style="{
                        bottom: `${meterPercent(range[0])}%`,
                        top: `${100 - meterPercent(range[1])}%`,
                    }"
                />
                <div
                    class="absolute inset-x-0 bottom-0 rounded-full transition-[height] duration-75"
                    :class="inRange ? 'bg-emerald-500' : 'bg-sky-400'"
                    :style="{ height: `${meterPercent(level)}%` }"
                />
            </div>

            <div
                class="relative flex-1 overflow-hidden rounded-3xl bg-gradient-to-b from-sky-200 to-sky-50 dark:from-sky-900 dark:to-sky-950"
            >
                <span
                    class="absolute top-4 left-1/2 -translate-x-1/2 text-5xl"
                    aria-hidden="true"
                >
                    ☁️
                </span>
                <span
                    class="absolute left-1/2 text-[7rem] leading-none transition-[bottom] duration-100 sm:text-[9rem]"
                    :class="{
                        'animate-bounce': phase === 'done',
                    }"
                    :style="{
                        bottom: `calc(${height * 75}% + 0.5rem)`,
                        transform: 'translateX(-50%)',
                    }"
                    aria-hidden="true"
                >
                    🎈
                </span>
                <p
                    v-if="phase === 'done'"
                    class="absolute inset-x-0 top-1/3 text-center text-4xl font-bold"
                >
                    {{ isMalay ? 'Hebat!' : 'You did it!' }}
                </p>
            </div>
        </div>

        <div
            v-if="phase === 'playing' || phase === 'done'"
            class="flex flex-wrap items-center justify-center gap-3 border-t bg-muted/60 p-3 text-center"
        >
            <p class="text-xs text-muted-foreground">
                Try {{ prompt.balloon?.tryNumber }} · keep the voice in the
                green zone for {{ holdSeconds }}
                {{ holdSeconds === 1 ? 'second' : 'seconds' }} to lift the
                balloon. Levels are relative to the calibration, not decibels.
            </p>
            <div class="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="disabled || phase !== 'playing'"
                    @click="notThisTime"
                >
                    <SkipForward />
                    Not this time
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="disabled || phase !== 'playing'"
                    @click="calibrate"
                >
                    <RotateCcw />
                    Calibrate again
                </Button>
            </div>
        </div>
    </div>
</template>
