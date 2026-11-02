<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeftRight,
    ArrowUp,
    BookOpen,
    Plus,
    Search,
    ShieldCheck,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { index } from '@/actions/App/Http/Controllers/ActivityController';
import { index as library } from '@/actions/App/Http/Controllers/ContentItemController';
import InputError from '@/components/InputError.vue';
import BackgroundSettings from '@/components/therapy/BackgroundSettings.vue';
import DirectionsEditor from '@/components/therapy/DirectionsEditor.vue';
import PictureCard from '@/components/therapy/PictureCard.vue';
import RewardSettings from '@/components/therapy/RewardSettings.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { useSpeech } from '@/composables/useSpeech';
import { categoryIcon, categoryLabel } from '@/lib/therapy';
import { cn } from '@/lib/utils';
import type {
    ActivityBuilderOptions,
    ActivityInput,
    LibraryItem,
} from '@/types';

const props = defineProps<{
    options: ActivityBuilderOptions;
    library: LibraryItem[];
    initialValues: ActivityInput;
    action: { url: string; method: 'post' | 'put' };
    submitLabel: string;
    /** The background saved with the activity being edited. */
    backgroundUrl?: string | null;
}>();

const anyValue = 'any';

const form = useForm<ActivityInput>({
    ...props.initialValues,
    settings: {
        reward_every: 0,
        ...props.initialValues.settings,
        categories: [...(props.initialValues.settings.categories ?? [])],
    },
    items: props.initialValues.items.map((item) => ({ ...item })),
    directions: (props.initialValues.directions ?? []).map((direction) => ({
        ...direction,
        item_ids: [...direction.item_ids],
    })),
    background: null,
    remove_background: false,
});

const errors = computed(
    () => form.errors as Record<string, string | undefined>,
);

const libraryById = computed(
    () => new Map(props.library.map((item) => [item.id, item])),
);

const selectedGameType = computed(() =>
    props.options.gameTypes.find(
        (gameType) => gameType.value === form.game_type,
    ),
);
const usesPairs = computed(() => selectedGameType.value?.uses_pairs ?? false);
const usesStories = computed(
    () => selectedGameType.value?.uses_stories ?? false,
);
const isFindIt = computed(() => form.game_type === 'find_it');
const usesWords = computed(() => selectedGameType.value?.uses_words ?? true);
const usesCategories = computed(
    () => selectedGameType.value?.uses_categories ?? false,
);
const usesDirections = computed(
    () => selectedGameType.value?.uses_directions ?? false,
);
const isBalloonVoice = computed(() => form.game_type === 'balloon_voice');
const usesSounds = computed(() => selectedGameType.value?.uses_sounds ?? false);
const maxBoardSize = 8;

const trialCountLabel = computed(() => {
    if (usesStories.value) {
        return 'Stories per session';
    }

    return usesDirections.value
        ? 'Directions per session'
        : 'Tries per session';
});

const fieldSizes = computed(() =>
    usesSounds.value ? ['2', '3', '4'] : ['3', '4', '5', '6'],
);
const fieldSize = computed({
    get: () => String(form.settings.field_size ?? 4),
    set: (value: string) => {
        form.settings.field_size = Number(value);
    },
});

const speech = useSpeech(() => form.language);

watch(
    () => form.game_type,
    () => {
        if (isFindIt.value) {
            const size = Number(form.settings.field_size ?? 4);
            form.settings.field_size = size < 3 ? 4 : size;
        }

        if (usesSounds.value) {
            const size = Number(form.settings.field_size ?? 3);
            form.settings.field_size = size > 4 ? 3 : size;
        }

        if (isFindIt.value || usesDirections.value) {
            form.settings.speak_word ??= false;
        }

        if (isBalloonVoice.value) {
            form.settings.target_level ??= 'normal';
            form.settings.hold_seconds ??= 3;
        }
    },
    { immediate: true },
);

watch(
    () => Number(form.settings.reward_every ?? 0),
    (rewardEvery) => {
        if (rewardEvery > 0) {
            form.settings.sticker_set ??= props.options.stickerSets[0]?.value;
        }
    },
);

const voiceLevel = computed({
    get: () => form.settings.target_level ?? 'normal',
    set: (value: string) => {
        form.settings.target_level = value as typeof form.settings.target_level;
    },
});

watch(speech.hasVoice, (hasVoice) => {
    if (!hasVoice && speech.isSupported) {
        form.settings.speak_word = false;
    }
});

const languageLibrary = computed(() =>
    props.library.filter((item) => item.language === form.language),
);

const search = ref('');
const soundFilter = ref(anyValue);
const positionFilter = ref(anyValue);

const sounds = computed(() =>
    [
        ...new Set(
            languageLibrary.value
                .map((item) => item.target_sound)
                .filter((sound): sound is string => !!sound),
        ),
    ].sort(),
);

/** Word groups the clinician can sort into: every group with a non-story picture. */
const sortingGroups = computed(() => {
    const counts = new Map<string, number>();

    for (const item of languageLibrary.value) {
        if (item.category && !item.story_key) {
            counts.set(item.category, (counts.get(item.category) ?? 0) + 1);
        }
    }

    return [...counts]
        .map(([category, count]) => ({ category, count }))
        .sort((first, second) => first.category.localeCompare(second.category));
});

const chosenGroups = computed(() => form.settings.categories ?? []);

function toggleGroup(category: string): void {
    if (chosenGroups.value.includes(category)) {
        form.settings.categories = chosenGroups.value.filter(
            (chosen) => chosen !== category,
        );
        form.items = form.items.filter(
            (item) =>
                libraryById.value.get(item.content_item_id)?.category !==
                category,
        );

        return;
    }

    if (chosenGroups.value.length < 3) {
        form.settings.categories = [...chosenGroups.value, category];
    }
}

function addWholeGroup(category: string): void {
    const selected = new Set(form.items.map((item) => item.content_item_id));

    for (const item of languageLibrary.value) {
        if (
            item.category === category &&
            !item.story_key &&
            !selected.has(item.id)
        ) {
            form.items.push({ content_item_id: item.id, paired_item_id: null });
        }
    }
}

const boardPictures = computed(() =>
    usesDirections.value
        ? form.items
              .map((item) => libraryById.value.get(item.content_item_id))
              .filter((item): item is LibraryItem => !!item)
        : [],
);

// Removing a picture from the board removes it from every direction.
watch(
    () => form.items.map((item) => item.content_item_id),
    (boardIds) => {
        if (!usesDirections.value) {
            return;
        }

        const onBoard = new Set(boardIds);

        for (const direction of form.directions) {
            if (direction.item_ids.some((id) => !onBoard.has(id))) {
                direction.item_ids = direction.item_ids.filter((id) =>
                    onBoard.has(id),
                );
            }
        }
    },
);

const filteredLibrary = computed(() => {
    const term = search.value.trim().toLowerCase();

    return languageLibrary.value.filter(
        (item) =>
            (!usesSounds.value || !!item.sound_url) &&
            (!usesCategories.value ||
                (chosenGroups.value.length > 0 &&
                    !!item.category &&
                    chosenGroups.value.includes(item.category) &&
                    !item.story_key)) &&
            (!term || item.word.toLowerCase().includes(term)) &&
            (soundFilter.value === anyValue ||
                item.target_sound === soundFilter.value) &&
            (positionFilter.value === anyValue ||
                item.word_position === positionFilter.value),
    );
});

const suggestedPairs = computed(() => {
    const groups = new Map<string, LibraryItem[]>();

    for (const item of languageLibrary.value) {
        if (item.pair_key) {
            groups.set(item.pair_key, [
                ...(groups.get(item.pair_key) ?? []),
                item,
            ]);
        }
    }

    return [...groups.values()].filter((group) => group.length === 2) as [
        LibraryItem,
        LibraryItem,
    ][];
});

/** The story new cards are added to, in story sequence mode. */
const activeStory = ref(
    Math.max(1, ...form.items.map((item) => item.group_number ?? 1)),
);

const storyNumbers = computed(() =>
    [
        ...new Set([
            ...form.items.map((item) => item.group_number ?? 1),
            activeStory.value,
        ]),
    ].sort((first, second) => first - second),
);

function storyItems(storyNumber: number) {
    return form.items
        .map((item, itemIndex) => ({ item, itemIndex }))
        .filter(({ item }) => item.group_number === storyNumber);
}

const suggestedStories = computed(() => {
    const groups = new Map<string, LibraryItem[]>();

    for (const item of languageLibrary.value) {
        if (item.story_key) {
            groups.set(item.story_key, [
                ...(groups.get(item.story_key) ?? []),
                item,
            ]);
        }
    }

    return [...groups].map(([key, cards]) => ({
        key,
        cards: [...cards].sort(
            (first, second) =>
                (first.story_position ?? 0) - (second.story_position ?? 0),
        ),
    }));
});

function nextStoryNumber(): number {
    return Math.max(0, ...form.items.map((item) => item.group_number ?? 0)) + 1;
}

function startStory(): void {
    activeStory.value = storyItems(activeStory.value).length
        ? nextStoryNumber()
        : activeStory.value;
}

function addSuggestedStory(cards: LibraryItem[]): void {
    const storyNumber = storyItems(activeStory.value).length
        ? nextStoryNumber()
        : activeStory.value;

    for (const card of cards.slice(0, 5)) {
        form.items.push({
            content_item_id: card.id,
            paired_item_id: null,
            group_number: storyNumber,
        });
    }

    activeStory.value = nextStoryNumber();
}

function moveEarlier(itemIndex: number, storyNumber: number): void {
    const story = storyItems(storyNumber);
    const position = story.findIndex((entry) => entry.itemIndex === itemIndex);

    if (position <= 0) {
        return;
    }

    const previousIndex = story[position - 1].itemIndex;
    const items = [...form.items];
    [items[previousIndex], items[itemIndex]] = [
        items[itemIndex],
        items[previousIndex],
    ];
    form.items = items;
}

const selectedIds = computed(
    () =>
        new Set(
            (usesStories.value
                ? form.items.filter(
                      (item) => item.group_number === activeStory.value,
                  )
                : form.items
            ).map((item) => item.content_item_id),
        ),
);

const pendingFirstWord = ref<LibraryItem | null>(null);

watch(
    () => form.language,
    () => {
        form.items = form.items.filter(
            (item) =>
                libraryById.value.get(item.content_item_id)?.language ===
                form.language,
        );
        pendingFirstWord.value = null;
        soundFilter.value = anyValue;
        form.settings.categories = [];
        form.directions = form.directions.map((direction) => ({
            ...direction,
            item_ids: [],
        }));
    },
);

watch(usesStories, (storyMode) => {
    if (storyMode) {
        form.items = form.items.map((item, itemIndex) => ({
            ...item,
            paired_item_id: null,
            group_number: Math.floor(itemIndex / 5) + 1,
        }));
        activeStory.value = Math.max(
            1,
            ...form.items.map((item) => item.group_number ?? 1),
        );

        return;
    }

    const seen = new Set<number>();
    form.items = form.items
        .filter((item) => {
            const isDuplicate = seen.has(item.content_item_id);
            seen.add(item.content_item_id);

            return !isDuplicate;
        })
        .map((item) => ({ ...item, group_number: null }));
});

watch(usesPairs, (pairs) => {
    pendingFirstWord.value = null;

    if (!pairs) {
        form.items = form.items.map((item) => ({
            ...item,
            paired_item_id: null,
        }));
    }
});

function pickWord(item: LibraryItem): void {
    if (usesStories.value) {
        const story = storyItems(activeStory.value);
        const existing = story.find(
            (entry) => entry.item.content_item_id === item.id,
        );

        if (existing) {
            form.items.splice(existing.itemIndex, 1);
        } else if (story.length < 5) {
            form.items.push({
                content_item_id: item.id,
                paired_item_id: null,
                group_number: activeStory.value,
            });
        }

        return;
    }

    if (!usesPairs.value) {
        if (selectedIds.value.has(item.id)) {
            form.items = form.items.filter(
                (selected) => selected.content_item_id !== item.id,
            );
        } else if (!usesDirections.value || form.items.length < maxBoardSize) {
            form.items.push({ content_item_id: item.id, paired_item_id: null });
        }

        return;
    }

    if (!pendingFirstWord.value) {
        pendingFirstWord.value = item;

        return;
    }

    if (pendingFirstWord.value.id !== item.id) {
        addPair(pendingFirstWord.value, item);
    }

    pendingFirstWord.value = null;
}

function addPair(first: LibraryItem, second: LibraryItem): void {
    if (selectedIds.value.has(first.id)) {
        return;
    }

    form.items.push({ content_item_id: first.id, paired_item_id: second.id });
}

function removeItem(index: number): void {
    form.items.splice(index, 1);
}

function itemError(index: number): string | undefined {
    const errors = form.errors as Record<string, string | undefined>;

    return (
        errors[`items.${index}.content_item_id`] ??
        errors[`items.${index}.paired_item_id`] ??
        errors[`items.${index}.group_number`]
    );
}

function submit(): void {
    if (!form.background) {
        form.transform(({ background, ...data }) => data).submit(
            props.action.method,
            props.action.url,
            { preserveScroll: true },
        );

        return;
    }

    // Files can only be uploaded with POST, so an update is sent as a spoofed PUT.
    form.transform((data) => ({
        ...data,
        ...(props.action.method === 'put' ? { _method: 'put' } : {}),
    })).post(props.action.url, {
        preserveScroll: true,
        forceFormData: true,
    });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <Card>
            <CardHeader>
                <CardTitle>Game</CardTitle>
                <CardDescription>
                    Choose how the activity is played with the child
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-5">
                <div
                    role="radiogroup"
                    aria-label="Game type"
                    class="grid gap-3 sm:grid-cols-2"
                >
                    <button
                        v-for="gameType in options.gameTypes"
                        :key="gameType.value"
                        type="button"
                        role="radio"
                        :aria-checked="form.game_type === gameType.value"
                        :class="
                            cn(
                                'rounded-lg border p-4 text-left transition-colors',
                                form.game_type === gameType.value
                                    ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                    : 'hover:bg-muted/50',
                            )
                        "
                        @click="
                            form.game_type =
                                gameType.value as ActivityInput['game_type']
                        "
                    >
                        <p class="font-medium">{{ gameType.label }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ gameType.description }}
                        </p>
                    </button>
                </div>
                <InputError :message="form.errors.game_type" />

                <div
                    class="grid gap-4 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)]"
                >
                    <div class="grid gap-2">
                        <Label for="title">Title</Label>
                        <Input
                            id="title"
                            v-model="form.title"
                            placeholder="e.g. /s/ initial – words"
                            maxlength="100"
                            :aria-invalid="!!form.errors.title"
                        />
                        <InputError :message="form.errors.title" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="language">Language</Label>
                        <Select v-model="form.language">
                            <SelectTrigger id="language" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in options.languages"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.language" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="trial_count">
                            {{ trialCountLabel }}
                        </Label>
                        <Input
                            id="trial_count"
                            v-model="form.settings.trial_count"
                            type="number"
                            min="1"
                            max="100"
                            :aria-invalid="
                                !!(form.errors as Record<string, string>)[
                                    'settings.trial_count'
                                ]
                            "
                        />
                        <InputError
                            :message="
                                (form.errors as Record<string, string>)[
                                    'settings.trial_count'
                                ]
                            "
                        />
                    </div>
                </div>

                <div v-if="usesWords" class="flex flex-wrap gap-x-6 gap-y-2">
                    <Label class="flex items-center gap-2 font-normal">
                        <Checkbox v-model="form.settings.shuffle" />
                        {{
                            usesDirections
                                ? 'Shuffle the directions and the board'
                                : 'Shuffle the words'
                        }}
                    </Label>
                    <Label class="flex items-center gap-2 font-normal">
                        <Checkbox v-model="form.settings.show_word_text" />
                        Show the written word to the child
                    </Label>
                </div>

                <RewardSettings
                    v-model:reward-every="form.settings.reward_every"
                    v-model:sticker-set="form.settings.sticker_set"
                    :sticker-sets="options.stickerSets"
                    :error="errors['settings.sticker_set']"
                />

                <div
                    v-if="usesDirections"
                    class="grid gap-1 rounded-md border p-3"
                >
                    <Label
                        class="flex items-center gap-2 font-normal"
                        :class="{ 'opacity-60': !speech.hasVoice.value }"
                    >
                        <Checkbox
                            v-model="form.settings.speak_word"
                            :disabled="!speech.hasVoice.value"
                        />
                        Read each direction aloud
                    </Label>
                    <p class="text-xs text-muted-foreground">
                        <template v-if="speech.hasVoice.value">
                            Uses this device&rsquo;s voice. Repeating a
                            direction counts as a prompt.
                        </template>
                        <template v-else>
                            This device has no
                            {{ form.language === 'ms' ? 'Malay' : 'English' }}
                            voice, so you will read each direction yourself.
                        </template>
                    </p>
                </div>

                <div
                    v-if="usesCategories"
                    class="grid gap-2 rounded-md border p-3"
                >
                    <p class="text-sm font-medium">
                        Groups to sort into
                        <span class="font-normal text-muted-foreground">
                            · choose 2 or 3
                        </span>
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-for="group in sortingGroups"
                            :key="group.category"
                            type="button"
                            size="sm"
                            :variant="
                                chosenGroups.includes(group.category)
                                    ? 'default'
                                    : 'outline'
                            "
                            :aria-pressed="
                                chosenGroups.includes(group.category)
                            "
                            :disabled="
                                !chosenGroups.includes(group.category) &&
                                chosenGroups.length >= 3
                            "
                            @click="toggleGroup(group.category)"
                        >
                            {{ categoryIcon(group.category) }}
                            {{ categoryLabel(group.category) }}
                            <span class="text-xs opacity-70">
                                {{ group.count }}
                            </span>
                        </Button>
                    </div>
                    <div
                        v-if="chosenGroups.length"
                        class="flex flex-wrap gap-2 text-sm"
                    >
                        <Button
                            v-for="category in chosenGroups"
                            :key="category"
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="addWholeGroup(category)"
                        >
                            <Plus />
                            Add every {{ categoryLabel(category) }} picture
                        </Button>
                    </div>
                    <InputError
                        :message="
                            errors['settings.categories'] ??
                            errors['settings.categories.0']
                        "
                    />
                </div>

                <div
                    v-if="isBalloonVoice"
                    class="grid gap-4 rounded-md border p-3 sm:grid-cols-2"
                >
                    <div class="grid gap-2">
                        <Label for="target_level">Target voice</Label>
                        <Select v-model="voiceLevel">
                            <SelectTrigger id="target_level" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in options.voiceLevels"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError
                            :message="errors['settings.target_level']"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="hold_seconds">Hold for (seconds)</Label>
                        <Input
                            id="hold_seconds"
                            v-model="form.settings.hold_seconds"
                            type="number"
                            min="1"
                            max="10"
                            :aria-invalid="!!errors['settings.hold_seconds']"
                        />
                        <InputError
                            :message="errors['settings.hold_seconds']"
                        />
                    </div>
                    <p
                        class="flex items-start gap-2 text-sm text-muted-foreground sm:col-span-2"
                    >
                        <ShieldCheck class="mt-0.5 size-4 shrink-0" />
                        Uses the device microphone. Sound is measured on the
                        device only and is never recorded, stored or uploaded.
                        Levels are relative to a short calibration before
                        playing, not decibels.
                    </p>
                </div>

                <div
                    v-if="isFindIt || usesSounds"
                    class="flex flex-wrap items-start gap-x-6 gap-y-3 rounded-md border p-3"
                >
                    <div class="grid gap-2">
                        <Label for="field_size">Pictures to choose from</Label>
                        <Select v-model="fieldSize">
                            <SelectTrigger id="field_size" class="w-24">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="size in fieldSizes"
                                    :key="size"
                                    :value="size"
                                >
                                    {{ size }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError
                            :message="
                                (form.errors as Record<string, string>)[
                                    'settings.field_size'
                                ]
                            "
                        />
                    </div>
                    <div v-if="usesSounds" class="grid max-w-md gap-1 text-sm">
                        <p class="font-medium">How it plays</p>
                        <p class="text-muted-foreground">
                            Each animal&rsquo;s sound plays by itself, and the
                            child taps the animal that makes it. Tap 🔊 to play
                            it again.
                        </p>
                    </div>
                    <div v-if="isFindIt" class="grid gap-1">
                        <Label
                            class="flex items-center gap-2 font-normal"
                            :class="{ 'opacity-60': !speech.hasVoice.value }"
                        >
                            <Checkbox
                                v-model="form.settings.speak_word"
                                :disabled="!speech.hasVoice.value"
                            />
                            Speak the word aloud
                        </Label>
                        <p class="text-xs text-muted-foreground">
                            <template v-if="speech.hasVoice.value">
                                Uses this device&rsquo;s voice. You can still
                                say the word yourself.
                            </template>
                            <template v-else>
                                This device has no
                                {{
                                    form.language === 'ms' ? 'Malay' : 'English'
                                }}
                                voice, so you will say the word yourself.
                            </template>
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div
            v-if="usesWords"
            class="grid gap-6 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]"
        >
            <Card>
                <CardHeader>
                    <CardTitle>Word library</CardTitle>
                    <CardDescription>
                        <template v-if="usesCategories">
                            Choose the groups above, then tap pictures to add
                            them.
                        </template>
                        <template v-else-if="usesDirections">
                            Tap 4 to 8 pictures to put them on the board.
                        </template>
                        <template v-else-if="usesSounds">
                            Only pictures with a sound are shown. Tap at least 2
                            animals to add them.
                        </template>
                        <template v-else-if="usesStories">
                            Tap pictures in story order to add them to story
                            {{ activeStory }}.
                        </template>
                        <template v-else-if="usesPairs">
                            Tap the word you will say, then the contrasting
                            word.
                        </template>
                        <template v-else>
                            Tap words to add or remove them.
                        </template>
                        Need another word?
                        <Link
                            :href="
                                library({ query: { language: form.language } })
                            "
                            class="underline underline-offset-4"
                        >
                            Add it to the library
                        </Link>
                        .
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div
                        v-if="usesPairs && suggestedPairs.length"
                        class="space-y-2"
                    >
                        <p class="text-sm font-medium">Suggested pairs</p>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                v-for="[first, second] in suggestedPairs"
                                :key="`${first.id}-${second.id}`"
                                type="button"
                                variant="outline"
                                size="sm"
                                :disabled="selectedIds.has(first.id)"
                                @click="addPair(first, second)"
                            >
                                {{ first.emoji }} {{ first.word }}
                                <ArrowLeftRight class="size-3" />
                                {{ second.emoji }} {{ second.word }}
                            </Button>
                        </div>
                    </div>

                    <div
                        v-if="usesStories && suggestedStories.length"
                        class="space-y-2"
                    >
                        <p class="text-sm font-medium">Suggested stories</p>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                v-for="story in suggestedStories"
                                :key="story.key"
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="addSuggestedStory(story.cards)"
                            >
                                <span
                                    v-for="card in story.cards"
                                    :key="card.id"
                                    :title="card.word"
                                >
                                    {{ card.emoji }}
                                </span>
                                {{ story.cards[0]?.word }}…
                            </Button>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <div class="relative min-w-40 flex-1">
                            <Search
                                class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                v-model="search"
                                placeholder="Search"
                                class="pl-8"
                                aria-label="Search words"
                            />
                        </div>
                        <Select v-model="soundFilter">
                            <SelectTrigger
                                class="w-32"
                                aria-label="Filter by sound"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="anyValue"
                                    >Any sound</SelectItem
                                >
                                <SelectItem
                                    v-for="sound in sounds"
                                    :key="sound"
                                    :value="sound"
                                >
                                    /{{ sound }}/
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Select v-model="positionFilter">
                            <SelectTrigger
                                class="w-32"
                                aria-label="Filter by position"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="anyValue"
                                    >Any position</SelectItem
                                >
                                <SelectItem
                                    v-for="option in options.wordPositions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <p
                        v-if="usesSounds && filteredLibrary.length === 0"
                        class="rounded-md border border-dashed p-4 text-sm text-muted-foreground"
                    >
                        No pictures in this language have a sound yet. Add an
                        animal in the
                        <Link
                            :href="
                                library({ query: { language: form.language } })
                            "
                            class="font-medium text-foreground underline underline-offset-4"
                            >word library</Link
                        >
                        and upload its sound, such as a cow mooing.
                    </p>

                    <p
                        v-if="usesCategories && chosenGroups.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Choose the groups to sort into to see their pictures.
                    </p>

                    <p
                        v-if="pendingFirstWord"
                        class="rounded-md bg-primary/10 px-3 py-2 text-sm"
                    >
                        Now tap the contrasting word for
                        <strong>{{ pendingFirstWord.word }}</strong
                        >.
                    </p>

                    <ul
                        class="grid max-h-[28rem] grid-cols-3 gap-2 overflow-y-auto sm:grid-cols-4 xl:grid-cols-5"
                    >
                        <li v-for="item in filteredLibrary" :key="item.id">
                            <button
                                type="button"
                                :class="
                                    cn(
                                        'flex w-full flex-col items-center rounded-lg border p-2 transition-colors hover:bg-muted/50',
                                        selectedIds.has(item.id) &&
                                            'border-primary bg-primary/5',
                                        pendingFirstWord?.id === item.id &&
                                            'ring-2 ring-primary',
                                    )
                                "
                                :aria-pressed="selectedIds.has(item.id)"
                                @click="pickWord(item)"
                            >
                                <PictureCard
                                    :picture="item"
                                    size="sm"
                                    show-word
                                />
                                <span
                                    v-if="usesSounds"
                                    class="text-[10px] text-muted-foreground"
                                >
                                    🔊 sound
                                </span>
                                <span
                                    v-else-if="item.target_sound"
                                    class="text-[10px] text-muted-foreground"
                                >
                                    /{{ item.target_sound }}/
                                    {{ item.word_position ?? '' }}
                                </span>
                            </button>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card v-if="usesStories">
                <CardHeader>
                    <CardTitle>Stories in this activity</CardTitle>
                    <CardDescription>
                        Each story needs 3 to 5 cards, in the correct order ·
                        {{ form.settings.trial_count || 0 }} stories per session
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-for="storyNumber in storyNumbers"
                        :key="storyNumber"
                        :class="
                            cn(
                                'space-y-2 rounded-md border p-3',
                                storyNumber === activeStory &&
                                    'border-primary ring-1 ring-primary',
                            )
                        "
                    >
                        <button
                            type="button"
                            class="flex items-center gap-2 text-sm font-medium"
                            @click="activeStory = storyNumber"
                        >
                            <BookOpen class="size-4" />
                            Story {{ storyNumber }}
                            <span class="text-xs text-muted-foreground">
                                {{ storyItems(storyNumber).length }}/5
                                <template v-if="storyNumber === activeStory">
                                    · adding here
                                </template>
                            </span>
                        </button>
                        <p
                            v-if="storyItems(storyNumber).length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            Tap pictures in the library, or pick a suggested
                            story.
                        </p>
                        <ol class="space-y-1">
                            <li
                                v-for="(entry, position) in storyItems(
                                    storyNumber,
                                )"
                                :key="`${storyNumber}-${entry.item.content_item_id}`"
                                class="flex items-center gap-2 text-sm"
                            >
                                <span
                                    class="w-4 text-right text-muted-foreground tabular-nums"
                                >
                                    {{ position + 1 }}
                                </span>
                                <template
                                    v-if="
                                        libraryById.get(
                                            entry.item.content_item_id,
                                        )
                                    "
                                >
                                    <PictureCard
                                        :picture="
                                            libraryById.get(
                                                entry.item.content_item_id,
                                            )!
                                        "
                                        size="sm"
                                    />
                                    <span class="font-medium">
                                        {{
                                            libraryById.get(
                                                entry.item.content_item_id,
                                            )!.word
                                        }}
                                    </span>
                                </template>
                                <div class="ml-auto flex">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        :disabled="position === 0"
                                        aria-label="Move earlier"
                                        @click="
                                            moveEarlier(
                                                entry.itemIndex,
                                                storyNumber,
                                            )
                                        "
                                    >
                                        <ArrowUp />
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Remove"
                                        @click="removeItem(entry.itemIndex)"
                                    >
                                        <X />
                                    </Button>
                                </div>
                            </li>
                        </ol>
                        <InputError
                            v-for="entry in storyItems(storyNumber)"
                            :key="`error-${entry.itemIndex}`"
                            :message="itemError(entry.itemIndex)"
                        />
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="storyItems(activeStory).length === 0"
                        @click="startStory"
                    >
                        <Plus />
                        New story
                    </Button>
                    <InputError :message="form.errors.items" />
                </CardContent>
            </Card>

            <Card v-else>
                <CardHeader>
                    <CardTitle>
                        <template v-if="usesDirections">
                            Pictures on the board
                        </template>
                        <template v-else>
                            {{ usesPairs ? 'Pairs' : 'Words' }} in this activity
                        </template>
                    </CardTitle>
                    <CardDescription>
                        <template v-if="usesDirections">
                            {{ form.items.length }} of {{ maxBoardSize }}
                            pictures · at least 4
                        </template>
                        <template v-else>
                            {{ form.items.length }}
                            {{ usesPairs ? 'pairs' : 'words' }} ·
                            {{ form.settings.trial_count || 0 }} tries per
                            session
                        </template>
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-2">
                    <p
                        v-if="form.items.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Nothing added yet.
                    </p>
                    <ul class="space-y-2">
                        <li
                            v-for="(item, itemIndex) in form.items"
                            :key="`${item.content_item_id}-${item.paired_item_id}`"
                        >
                            <div
                                class="flex items-center gap-3 rounded-md border px-3 py-2"
                            >
                                <template
                                    v-if="libraryById.get(item.content_item_id)"
                                >
                                    <PictureCard
                                        :picture="
                                            libraryById.get(
                                                item.content_item_id,
                                            )!
                                        "
                                        size="sm"
                                    />
                                    <span class="font-medium">
                                        {{
                                            libraryById.get(
                                                item.content_item_id,
                                            )!.word
                                        }}
                                    </span>
                                    <span
                                        v-if="
                                            usesCategories &&
                                            libraryById.get(
                                                item.content_item_id,
                                            )!.category
                                        "
                                        class="rounded-full bg-muted px-2 py-0.5 text-xs"
                                    >
                                        {{
                                            categoryLabel(
                                                libraryById.get(
                                                    item.content_item_id,
                                                )!.category!,
                                            )
                                        }}
                                    </span>
                                </template>
                                <template
                                    v-if="
                                        item.paired_item_id &&
                                        libraryById.get(item.paired_item_id)
                                    "
                                >
                                    <ArrowLeftRight
                                        class="size-4 text-muted-foreground"
                                    />
                                    <PictureCard
                                        :picture="
                                            libraryById.get(
                                                item.paired_item_id,
                                            )!
                                        "
                                        size="sm"
                                    />
                                    <span class="font-medium">
                                        {{
                                            libraryById.get(
                                                item.paired_item_id,
                                            )!.word
                                        }}
                                    </span>
                                </template>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    class="ml-auto"
                                    aria-label="Remove"
                                    @click="removeItem(itemIndex)"
                                >
                                    <X />
                                </Button>
                            </div>
                            <InputError :message="itemError(itemIndex)" />
                        </li>
                    </ul>
                    <InputError :message="form.errors.items" />
                    <p
                        v-if="usesPairs && form.items.length === 0"
                        class="flex items-center gap-1 text-sm text-muted-foreground"
                    >
                        <Plus class="size-4" />
                        Add a suggested pair or tap two words.
                    </p>
                </CardContent>
            </Card>
        </div>

        <Card v-if="usesDirections">
            <CardHeader>
                <CardTitle>Directions</CardTitle>
                <CardDescription>
                    What you will say, and the pictures the child touches in
                    order. Progress is tracked by the number of steps.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <DirectionsEditor
                    v-model="form.directions"
                    :board="boardPictures"
                    :language="form.language"
                    :errors="errors"
                />
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Background</CardTitle>
                <CardDescription>
                    An optional picture shown behind the game, such as a beach,
                    a jungle or a classroom
                </CardDescription>
            </CardHeader>
            <CardContent>
                <BackgroundSettings
                    v-model:background="form.background"
                    v-model:remove-background="form.remove_background"
                    :current-url="backgroundUrl ?? null"
                    :error="form.errors.background"
                />
            </CardContent>
        </Card>

        <div class="flex items-center justify-end gap-2">
            <Button variant="ghost" as-child>
                <Link :href="index()">Cancel</Link>
            </Button>
            <Button type="submit" :disabled="form.processing">
                <Spinner v-if="form.processing" />
                {{ submitLabel }}
            </Button>
        </div>
    </form>
</template>
