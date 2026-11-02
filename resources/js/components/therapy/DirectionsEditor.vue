<script setup lang="ts">
import { Eraser, ListPlus, Plus, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PictureCard from '@/components/therapy/PictureCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { directionText } from '@/lib/therapy';
import { cn } from '@/lib/utils';
import type { DirectionInput, Picture } from '@/types';

const props = defineProps<{
    /** The pictures on the board, in the order they were added. */
    board: Picture[];
    language: string;
    errors: Record<string, string | undefined>;
}>();

const directions = defineModel<DirectionInput[]>({ required: true });

const maxSteps = 4;
const activeIndex = ref(Math.max(0, directions.value.length - 1));

const boardById = computed(
    () => new Map(props.board.map((picture) => [picture.id, picture])),
);

function stepPictures(direction: DirectionInput): Picture[] {
    return direction.item_ids
        .map((id) => boardById.value.get(id))
        .filter((picture): picture is Picture => !!picture);
}

function autoText(itemIds: number[]): string {
    return directionText(
        props.language,
        itemIds
            .map((id) => boardById.value.get(id)?.word)
            .filter((word): word is string => !!word),
    );
}

/** Change a direction's steps, keeping its text in step unless the clinician rewrote it. */
function setSteps(index: number, itemIds: number[]): void {
    const direction = directions.value[index];
    const followsSteps =
        direction.text.trim() === '' ||
        direction.text === autoText(direction.item_ids);

    directions.value = directions.value.map((current, currentIndex) =>
        currentIndex === index
            ? {
                  ...current,
                  item_ids: itemIds,
                  text: followsSteps ? autoText(itemIds) : current.text,
              }
            : current,
    );
}

/**
 * Add a direction and make it the one being edited. The model updates on the
 * next tick, so the new index is taken from the list built here.
 */
function addDirection(itemIds: number[] = []): void {
    const next = [
        ...directions.value,
        { id: null, text: autoText(itemIds), item_ids: itemIds },
    ];

    directions.value = next;
    activeIndex.value = next.length - 1;
}

function addOneStepDirections(): void {
    const existing = new Set(
        directions.value
            .filter((direction) => direction.item_ids.length === 1)
            .map((direction) => direction.item_ids[0]),
    );

    const next = [
        ...directions.value.filter(
            (direction) => direction.item_ids.length > 0,
        ),
        ...props.board
            .filter((picture) => !existing.has(picture.id))
            .map((picture) => ({
                id: null,
                text: autoText([picture.id]),
                item_ids: [picture.id],
            })),
    ];

    directions.value = next;
    activeIndex.value = Math.max(0, next.length - 1);
}

function removeDirection(index: number): void {
    const next = directions.value.filter((_, current) => current !== index);

    directions.value = next;
    activeIndex.value = Math.min(
        activeIndex.value,
        Math.max(0, next.length - 1),
    );
}

function tapBoardPicture(picture: Picture): void {
    if (directions.value.length === 0) {
        addDirection([picture.id]);

        return;
    }

    const direction = directions.value[activeIndex.value];

    if (
        !direction ||
        direction.item_ids.includes(picture.id) ||
        direction.item_ids.length >= maxSteps
    ) {
        return;
    }

    setSteps(activeIndex.value, [...direction.item_ids, picture.id]);
}

function directionError(index: number): string | undefined {
    return (
        props.errors[`directions.${index}.item_ids`] ??
        props.errors[`directions.${index}.text`] ??
        Object.entries(props.errors).find(([key]) =>
            key.startsWith(`directions.${index}.item_ids.`),
        )?.[1]
    );
}
</script>

<template>
    <div class="space-y-4">
        <div class="space-y-2">
            <p class="text-sm text-muted-foreground">
                <template v-if="board.length === 0">
                    Add pictures to the board first.
                </template>
                <template v-else>
                    Tap pictures in order to add steps to direction
                    {{ activeIndex + 1 }} (up to {{ maxSteps }}).
                </template>
            </p>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="picture in board"
                    :key="picture.id"
                    type="button"
                    class="rounded-lg border p-1.5 transition-colors hover:bg-muted/50"
                    :aria-label="`Add ${picture.word} as a step`"
                    @click="tapBoardPicture(picture)"
                >
                    <PictureCard :picture="picture" size="sm" show-word />
                </button>
            </div>
        </div>

        <ol class="space-y-2">
            <li
                v-for="(direction, index) in directions"
                :key="index"
                :class="
                    cn(
                        'space-y-2 rounded-md border p-3',
                        index === activeIndex &&
                            'border-primary ring-1 ring-primary',
                    )
                "
                @click="activeIndex = index"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-medium">{{ index + 1 }}.</span>
                    <span
                        v-if="direction.item_ids.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No steps yet
                    </span>
                    <span
                        v-for="(picture, step) in stepPictures(direction)"
                        :key="picture.id"
                        class="flex items-center gap-1 text-sm"
                    >
                        <span v-if="step > 0" class="text-muted-foreground">
                            →
                        </span>
                        <PictureCard :picture="picture" size="sm" />
                    </span>
                    <span class="text-xs text-muted-foreground">
                        {{ direction.item_ids.length }}
                        {{ direction.item_ids.length === 1 ? 'step' : 'steps' }}
                    </span>
                    <div class="ml-auto flex">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            aria-label="Clear steps"
                            :disabled="direction.item_ids.length === 0"
                            @click.stop="setSteps(index, [])"
                        >
                            <Eraser />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            aria-label="Remove direction"
                            @click.stop="removeDirection(index)"
                        >
                            <X />
                        </Button>
                    </div>
                </div>
                <Input
                    v-model="direction.text"
                    maxlength="150"
                    :placeholder="
                        language === 'ms'
                            ? 'e.g. Sentuh bola, kemudian kucing'
                            : 'e.g. Touch the ball, then the cat'
                    "
                    :aria-label="`What you say for direction ${index + 1}`"
                />
                <InputError :message="directionError(index)" />
            </li>
        </ol>

        <div class="flex flex-wrap gap-2">
            <Button
                type="button"
                variant="outline"
                size="sm"
                @click="addDirection()"
            >
                <Plus />
                Add direction
            </Button>
            <Button
                type="button"
                variant="outline"
                size="sm"
                :disabled="board.length === 0"
                @click="addOneStepDirections"
            >
                <ListPlus />
                Add a 1-step direction for each picture
            </Button>
        </div>
        <InputError :message="errors.directions" />
    </div>
</template>
