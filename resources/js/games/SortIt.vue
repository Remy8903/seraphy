<script setup lang="ts">
import { SkipForward } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import PictureCard from '@/components/therapy/PictureCard.vue';
import { Button } from '@/components/ui/button';
import { useGameSounds } from '@/composables/useGameSounds';
import { categoryIcon, categoryLabel } from '@/lib/therapy';
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

const feedbackDelayMs = 1000;
const dragThresholdPx = 6;
const { playAnswer } = useGameSounds();

const categories = computed(() => props.prompt.sorting?.categories ?? []);
const correctCategory = computed(() => props.prompt.sorting?.category ?? '');

/** The first wrong group chosen; a correct second try then scores as prompted. */
const firstWrongCategory = ref<string | null>(null);
const wrongBin = ref<string | null>(null);
const sortedInto = ref<string | null>(null);
const isLocked = computed(() => props.disabled || sortedInto.value !== null);

watch(
    () => props.prompt.key,
    () => {
        firstWrongCategory.value = null;
        wrongBin.value = null;
        sortedInto.value = null;
    },
);

function finish(
    result: TrialResultValue,
    category: string,
    answeredAt: number,
): void {
    sortedInto.value = category;
    playAnswer(result);

    const meta = firstWrongCategory.value
        ? { chosen_category: firstWrongCategory.value }
        : null;

    setTimeout(() => emit('answer', result, meta, answeredAt), feedbackDelayMs);
}

/** Sort the picture into a group. The first wrong group bounces it back for one more try. */
function sortInto(category: string): void {
    if (isLocked.value) {
        return;
    }

    const answeredAt = performance.now();

    if (category === correctCategory.value) {
        finish(
            firstWrongCategory.value ? 'prompted' : 'correct',
            category,
            answeredAt,
        );

        return;
    }

    if (firstWrongCategory.value === null) {
        firstWrongCategory.value = category;
        wrongBin.value = category;
        playAnswer('incorrect');
        setTimeout(() => {
            wrongBin.value = null;
        }, 600);

        return;
    }

    wrongBin.value = category;
    finish('incorrect', correctCategory.value, answeredAt);
}

function skip(): void {
    if (!isLocked.value) {
        finish('incorrect', correctCategory.value, performance.now());
    }
}

type Drag = {
    startX: number;
    startY: number;
    x: number;
    y: number;
    moved: boolean;
};

const drag = ref<Drag | null>(null);

function startDrag(event: PointerEvent): void {
    if (isLocked.value || event.button > 0) {
        return;
    }

    drag.value = {
        startX: event.clientX,
        startY: event.clientY,
        x: event.clientX,
        y: event.clientY,
        moved: false,
    };
}

function moveDrag(event: PointerEvent): void {
    const current = drag.value;

    if (!current) {
        return;
    }

    current.x = event.clientX;
    current.y = event.clientY;
    current.moved ||=
        Math.hypot(
            event.clientX - current.startX,
            event.clientY - current.startY,
        ) > dragThresholdPx;
}

function endDrag(event: PointerEvent): void {
    const current = drag.value;
    drag.value = null;

    if (!current?.moved) {
        return;
    }

    const bin = document
        .elementFromPoint(event.clientX, event.clientY)
        ?.closest<HTMLElement>('[data-sort-bin]');

    if (bin?.dataset.sortBin) {
        sortInto(bin.dataset.sortBin);
    }
}

function cancelDrag(): void {
    drag.value = null;
}

onMounted(() => {
    window.addEventListener('pointermove', moveDrag);
    window.addEventListener('pointerup', endDrag);
    window.addEventListener('pointercancel', cancelDrag);
});

onBeforeUnmount(() => {
    window.removeEventListener('pointermove', moveDrag);
    window.removeEventListener('pointerup', endDrag);
    window.removeEventListener('pointercancel', cancelDrag);
});

function binClasses(category: string): string {
    if (wrongBin.value === category) {
        return 'animate-[shake_0.4s_ease-in-out] border-rose-500 bg-rose-50 dark:bg-rose-950/40';
    }

    if (sortedInto.value === category) {
        return 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40';
    }

    return 'border-dashed border-muted-foreground/40 bg-muted/40 hover:border-primary';
}
</script>

<template>
    <div class="flex flex-1 flex-col select-none">
        <div class="flex flex-1 items-center justify-center p-6">
            <button
                v-if="!sortedInto"
                type="button"
                class="touch-none rounded-3xl border-4 border-border bg-background p-4 shadow-sm"
                :class="{ 'opacity-30': drag?.moved }"
                :aria-label="`${prompt.target.word}: drag it into a group, or tap a group`"
                :disabled="isLocked"
                @pointerdown="startDrag"
            >
                <PictureCard
                    :picture="prompt.target"
                    size="xl"
                    :show-word="showWordText"
                />
            </button>
        </div>

        <div
            class="grid gap-3 px-4 pb-4"
            :class="categories.length === 3 ? 'grid-cols-3' : 'grid-cols-2'"
        >
            <button
                v-for="category in categories"
                :key="category"
                type="button"
                :data-sort-bin="category"
                :disabled="isLocked"
                :class="[
                    'flex min-h-36 flex-col items-center justify-center gap-2 rounded-3xl border-4 p-3 transition-colors sm:min-h-44',
                    binClasses(category),
                ]"
                :aria-label="`Put it in ${categoryLabel(category)}`"
                @click="sortInto(category)"
            >
                <PictureCard
                    v-if="sortedInto === category"
                    :picture="prompt.target"
                    size="md"
                />
                <span
                    v-else-if="categoryIcon(category)"
                    class="text-6xl leading-none"
                >
                    {{ categoryIcon(category) }}
                </span>
                <span class="text-2xl font-bold">
                    {{ categoryLabel(category) }}
                </span>
            </button>
        </div>

        <div
            v-if="drag?.moved"
            class="pointer-events-none fixed z-50 -translate-x-1/2 -translate-y-1/2 opacity-90"
            :style="{ left: `${drag.x}px`, top: `${drag.y}px` }"
        >
            <PictureCard :picture="prompt.target" size="lg" />
        </div>

        <div
            class="flex items-center justify-center gap-3 border-t bg-muted/60 p-3"
        >
            <p class="text-xs text-muted-foreground">
                Clinician: let the child drag (or tap) the picture into its
                group.
                <template v-if="firstWrongCategory && !sortedInto">
                    One more try.
                </template>
            </p>
            <Button
                variant="outline"
                size="sm"
                :disabled="isLocked"
                @click="skip"
            >
                <SkipForward />
                Skip
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
