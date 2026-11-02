<script setup lang="ts">
import { Check, Lightbulb, SkipForward } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import PictureCard from '@/components/therapy/PictureCard.vue';
import { Button } from '@/components/ui/button';
import { useGameSounds } from '@/composables/useGameSounds';
import type { GamePrompt, Picture, TrialMeta, TrialResultValue } from '@/types';

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

const feedbackDelayMs = 1200;
const dragThresholdPx = 6;
const { playAnswer } = useGameSounds();

/** Where a card currently is: the shuffled pool, or a numbered slot. */
type Origin = 'pool' | number;

const cards = computed(() => props.prompt.story?.cards ?? []);
const slots = ref<(Picture | null)[]>([]);
const attempts = ref(1);
const wasHelped = ref(false);
const wrongSlots = ref(new Set<number>());
const selected = ref<{ picture: Picture; from: Origin } | null>(null);
const isSolved = ref(false);

const pool = computed(() =>
    props.prompt.options.filter(
        (option) => !slots.value.some((slotted) => slotted?.id === option.id),
    ),
);
const isFull = computed(() => slots.value.every((slotted) => slotted !== null));
const isLocked = computed(() => props.disabled || isSolved.value);

function reset(): void {
    slots.value = cards.value.map(() => null);
    attempts.value = 1;
    wasHelped.value = false;
    wrongSlots.value = new Set();
    selected.value = null;
    isSolved.value = false;
}

watch(() => props.prompt.key, reset, { immediate: true });

function place(picture: Picture, from: Origin, slotIndex: number): void {
    const occupant = slots.value[slotIndex];

    if (typeof from === 'number') {
        slots.value[from] = occupant;
    }

    slots.value[slotIndex] = picture;
    wrongSlots.value.delete(slotIndex);
    selected.value = null;
}

function returnToPool(slotIndex: number): void {
    slots.value[slotIndex] = null;
    selected.value = null;
}

function tapCard(picture: Picture, from: Origin): void {
    if (isLocked.value) {
        return;
    }

    if (from === 'pool') {
        selected.value =
            selected.value?.picture.id === picture.id
                ? null
                : { picture, from };

        return;
    }

    if (selected.value && selected.value.picture.id !== picture.id) {
        place(selected.value.picture, selected.value.from, from);

        return;
    }

    returnToPool(from);
}

function tapSlot(slotIndex: number): void {
    if (!isLocked.value && selected.value) {
        place(selected.value.picture, selected.value.from, slotIndex);
    }
}

type Drag = {
    picture: Picture;
    from: Origin;
    startX: number;
    startY: number;
    x: number;
    y: number;
    moved: boolean;
};

const drag = ref<Drag | null>(null);

function startDrag(event: PointerEvent, picture: Picture, from: Origin): void {
    if (isLocked.value || event.button > 0) {
        return;
    }

    drag.value = {
        picture,
        from,
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

    if (!current) {
        return;
    }

    if (!current.moved) {
        tapCard(current.picture, current.from);

        return;
    }

    const slot = document
        .elementFromPoint(event.clientX, event.clientY)
        ?.closest<HTMLElement>('[data-story-slot]');

    if (slot) {
        place(current.picture, current.from, Number(slot.dataset.storySlot));
    } else if (typeof current.from === 'number') {
        returnToPool(current.from);
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

function finish(result: TrialResultValue, answeredAt: number): void {
    isSolved.value = true;
    setTimeout(
        () => emit('answer', result, { attempts: attempts.value }, answeredAt),
        feedbackDelayMs,
    );
}

/**
 * Check the order. A wrong order sends the misplaced cards back to the pool
 * for another try; right first time without help scores as correct.
 */
function check(): void {
    if (isLocked.value || !isFull.value) {
        return;
    }

    const answeredAt = performance.now();
    const wrong = new Set(
        slots.value
            .map((slotted, index) =>
                slotted?.id === cards.value[index]?.id ? -1 : index,
            )
            .filter((index) => index >= 0),
    );

    if (wrong.size === 0) {
        const result =
            attempts.value === 1 && !wasHelped.value ? 'correct' : 'prompted';
        playAnswer(result);
        finish(result, answeredAt);

        return;
    }

    playAnswer('incorrect');
    wrongSlots.value = wrong;
    attempts.value++;

    setTimeout(() => {
        for (const index of wrong) {
            if (wrongSlots.value.has(index)) {
                slots.value[index] = null;
            }
        }

        wrongSlots.value = new Set();
    }, 700);
}

/** Put the next correct card in place for the child. */
function help(): void {
    if (isLocked.value) {
        return;
    }

    const index = slots.value.findIndex(
        (slotted, slotIndex) => slotted?.id !== cards.value[slotIndex]?.id,
    );

    if (index < 0) {
        return;
    }

    const card = cards.value[index];
    const currentSlot = slots.value.findIndex(
        (slotted) => slotted?.id === card.id,
    );

    wasHelped.value = true;
    place(card, currentSlot >= 0 ? currentSlot : 'pool', index);
}

function skip(): void {
    if (!isLocked.value) {
        playAnswer('incorrect');
        finish('incorrect', performance.now());
    }
}
</script>

<template>
    <div class="flex flex-1 flex-col select-none">
        <div class="flex flex-1 flex-col items-center justify-center gap-8 p-6">
            <ol class="flex flex-wrap justify-center gap-3">
                <li
                    v-for="(slotted, index) in slots"
                    :key="`${prompt.key}-slot-${index}`"
                    :data-story-slot="index"
                    :class="[
                        'relative flex min-h-44 min-w-40 items-center justify-center rounded-3xl border-4 p-3 transition-colors sm:min-h-56 sm:min-w-48',
                        slotted
                            ? 'border-solid'
                            : 'border-dashed border-muted-foreground/40 bg-muted/40',
                        wrongSlots.has(index)
                            ? 'animate-[shake_0.4s_ease-in-out] border-rose-500 bg-rose-50 dark:bg-rose-950/40'
                            : isSolved && slotted
                              ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40'
                              : slotted
                                ? 'border-border'
                                : '',
                        selected && !slotted ? 'border-primary' : '',
                    ]"
                    @click="tapSlot(index)"
                >
                    <span
                        class="absolute top-2 left-3 text-lg font-bold text-muted-foreground"
                    >
                        {{ index + 1 }}
                    </span>
                    <button
                        v-if="slotted"
                        type="button"
                        class="touch-none"
                        :class="{
                            'opacity-30':
                                drag?.moved && drag.picture.id === slotted.id,
                            'rounded-2xl ring-4 ring-primary':
                                selected?.picture.id === slotted.id,
                        }"
                        :aria-label="`${slotted.word}, in place ${index + 1}`"
                        @pointerdown="startDrag($event, slotted, index)"
                        @click.stop
                    >
                        <PictureCard
                            :picture="slotted"
                            size="lg"
                            :show-word="showWordText"
                        />
                    </button>
                </li>
            </ol>

            <ul
                class="flex min-h-20 flex-wrap justify-center gap-3 rounded-2xl bg-muted/40 p-3"
                aria-label="Story cards to place"
            >
                <li v-for="option in pool" :key="`${prompt.key}-${option.id}`">
                    <button
                        type="button"
                        class="touch-none rounded-2xl border-2 border-transparent bg-background p-2 shadow-sm"
                        :class="{
                            'opacity-30':
                                drag?.moved && drag.picture.id === option.id,
                            'border-primary ring-4 ring-primary':
                                selected?.picture.id === option.id,
                        }"
                        :disabled="isLocked"
                        :aria-label="option.word"
                        :aria-pressed="selected?.picture.id === option.id"
                        @pointerdown="startDrag($event, option, 'pool')"
                    >
                        <PictureCard
                            :picture="option"
                            size="lg"
                            :show-word="showWordText"
                        />
                    </button>
                </li>
            </ul>
        </div>

        <div
            v-if="drag?.moved"
            class="pointer-events-none fixed z-50 -translate-x-1/2 -translate-y-1/2 opacity-90"
            :style="{ left: `${drag.x}px`, top: `${drag.y}px` }"
        >
            <PictureCard
                :picture="drag.picture"
                size="lg"
                :show-word="showWordText"
            />
        </div>

        <div class="border-t bg-muted/60 p-3">
            <p class="mb-2 text-center text-xs text-muted-foreground">
                Clinician: let the child drag (or tap) the pictures into order,
                then check · try {{ attempts }}
            </p>
            <div class="mx-auto flex max-w-xl flex-wrap justify-center gap-2">
                <Button
                    size="lg"
                    :disabled="isLocked || !isFull"
                    class="bg-emerald-600 text-white hover:bg-emerald-700"
                    @click="check"
                >
                    <Check />
                    Check
                </Button>
                <Button
                    size="lg"
                    variant="outline"
                    :disabled="isLocked"
                    @click="help"
                >
                    <Lightbulb />
                    Help
                </Button>
                <Button
                    size="lg"
                    variant="outline"
                    :disabled="isLocked"
                    @click="skip"
                >
                    <SkipForward />
                    Skip
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
