<script setup lang="ts">
import { Form, Head, Link, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import {
    destroy,
    index,
    store,
    update,
} from '@/actions/App/Http/Controllers/ContentItemController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PictureCard from '@/components/therapy/PictureCard.vue';
import PrivacyNotice from '@/components/therapy/PrivacyNotice.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import type { LibraryItem, SelectOption } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Word library', href: index() }],
    },
});

const props = defineProps<{
    language: string;
    items: LibraryItem[];
    options: {
        languages: SelectOption[];
        wordPositions: SelectOption[];
    };
}>();

const anyValue = 'any';
const noPosition = 'none';

const search = ref('');
const soundFilter = ref(anyValue);
const positionFilter = ref(anyValue);
const showOnlyMine = ref(false);

const sounds = computed(() =>
    [
        ...new Set(
            props.items
                .map((item) => item.target_sound)
                .filter((sound): sound is string => !!sound),
        ),
    ].sort(),
);

const visibleItems = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.items.filter(
        (item) =>
            (!term ||
                item.word.toLowerCase().includes(term) ||
                item.category?.toLowerCase().includes(term)) &&
            (soundFilter.value === anyValue ||
                item.target_sound === soundFilter.value) &&
            (positionFilter.value === anyValue ||
                item.word_position === positionFilter.value) &&
            (!showOnlyMine.value || !item.is_starter),
    );
});

const editingItem = ref<LibraryItem | null>(null);
const isWordDialogOpen = ref(false);

const wordForm = useForm({
    language: props.language,
    word: '',
    emoji: '',
    image: null as File | null,
    remove_image: false,
    target_sound: '',
    word_position: noPosition,
    category: '',
});

function openNewWord(): void {
    editingItem.value = null;
    wordForm.defaults({
        language: props.language,
        word: '',
        emoji: '',
        image: null,
        remove_image: false,
        target_sound: '',
        word_position: noPosition,
        category: '',
    });
    wordForm.reset();
    wordForm.clearErrors();
    isWordDialogOpen.value = true;
}

function openEditWord(item: LibraryItem): void {
    editingItem.value = item;
    wordForm.defaults({
        language: item.language,
        word: item.word,
        emoji: item.emoji ?? '',
        image: null,
        remove_image: false,
        target_sound: item.target_sound ?? '',
        word_position: item.word_position ?? noPosition,
        category: item.category ?? '',
    });
    wordForm.reset();
    wordForm.clearErrors();
    isWordDialogOpen.value = true;
}

function selectImage(event: Event): void {
    wordForm.image = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function saveWord(): void {
    const editing = editingItem.value;

    wordForm
        .transform((data) => ({
            ...data,
            word_position:
                data.word_position === noPosition ? null : data.word_position,
            ...(editing ? { _method: 'put' } : {}),
        }))
        .post(editing ? update.url(editing.id) : store.url(), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                isWordDialogOpen.value = false;
            },
        });
}

const itemToDelete = ref<LibraryItem | null>(null);
</script>

<template>
    <Head title="Word library" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Word library"
                description="Starter words in Bahasa Melayu and English, plus words you add yourself"
            />
            <Button @click="openNewWord">
                <Plus />
                Add word
            </Button>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex gap-1 rounded-lg bg-muted p-1">
                <Link
                    v-for="option in options.languages"
                    :key="option.value"
                    :href="index({ query: { language: option.value } })"
                    :class="[
                        'rounded-md px-3.5 py-1.5 text-sm transition-colors',
                        language === option.value
                            ? 'bg-background font-medium shadow-xs'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                    preserve-scroll
                >
                    {{ option.label }}
                </Link>
            </div>

            <div class="relative min-w-48 flex-1 sm:max-w-64">
                <Search
                    class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    placeholder="Search words or categories"
                    class="pl-8"
                    aria-label="Search words"
                />
            </div>

            <Select v-model="soundFilter">
                <SelectTrigger class="w-36" aria-label="Filter by sound">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="anyValue">Any sound</SelectItem>
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
                <SelectTrigger class="w-36" aria-label="Filter by position">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="anyValue">Any position</SelectItem>
                    <SelectItem
                        v-for="option in options.wordPositions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Label class="flex items-center gap-2 font-normal">
                <Checkbox v-model="showOnlyMine" />
                My words only
            </Label>
        </div>

        <Card>
            <CardContent>
                <p
                    v-if="visibleItems.length === 0"
                    class="py-10 text-center text-sm text-muted-foreground"
                >
                    No words match these filters.
                </p>
                <ul
                    v-else
                    class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6"
                >
                    <li
                        v-for="item in visibleItems"
                        :key="item.id"
                        class="group relative flex flex-col items-center gap-2 rounded-lg border p-3 text-center"
                    >
                        <PictureCard :picture="item" size="md" show-word />
                        <div class="flex flex-wrap justify-center gap-1">
                            <Badge v-if="item.target_sound" variant="secondary">
                                /{{ item.target_sound }}/
                                <template v-if="item.word_position_label">
                                    {{ item.word_position_label.toLowerCase() }}
                                </template>
                            </Badge>
                            <Badge v-if="item.category" variant="outline">
                                {{ item.category }}
                            </Badge>
                            <Badge v-if="!item.is_starter">Mine</Badge>
                        </div>
                        <div
                            v-if="!item.is_starter"
                            class="absolute top-1 right-1 flex gap-0.5"
                        >
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-7"
                                :aria-label="`Edit ${item.word}`"
                                @click="openEditWord(item)"
                            >
                                <Pencil class="size-3.5" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-7"
                                :aria-label="`Delete ${item.word}`"
                                @click="itemToDelete = item"
                            >
                                <Trash2 class="size-3.5" />
                            </Button>
                        </div>
                    </li>
                </ul>
            </CardContent>
        </Card>

        <Dialog v-model:open="isWordDialogOpen">
            <DialogContent class="max-h-[90vh] overflow-y-auto">
                <form class="space-y-5" @submit.prevent="saveWord">
                    <DialogHeader>
                        <DialogTitle>
                            {{ editingItem ? 'Edit word' : 'Add word' }}
                        </DialogTitle>
                        <DialogDescription>
                            Use an emoji or upload a picture. Tag the target
                            sound so you can find the word when building
                            activities.
                        </DialogDescription>
                    </DialogHeader>

                    <PrivacyNotice context="upload" />

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="word">Word</Label>
                            <Input
                                id="word"
                                v-model="wordForm.word"
                                :aria-invalid="!!wordForm.errors.word"
                            />
                            <InputError :message="wordForm.errors.word" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="word_language">Language</Label>
                            <Select v-model="wordForm.language">
                                <SelectTrigger
                                    id="word_language"
                                    class="w-full"
                                >
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
                            <InputError :message="wordForm.errors.language" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="emoji">Emoji</Label>
                            <Input
                                id="emoji"
                                v-model="wordForm.emoji"
                                placeholder="🪑"
                                maxlength="16"
                                :aria-invalid="!!wordForm.errors.emoji"
                            />
                            <InputError :message="wordForm.errors.emoji" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="image">Picture</Label>
                            <Input
                                id="image"
                                type="file"
                                accept="image/png,image/jpeg,image/webp"
                                :aria-invalid="!!wordForm.errors.image"
                                @change="selectImage"
                            />
                            <InputError :message="wordForm.errors.image" />
                        </div>
                    </div>

                    <Label
                        v-if="editingItem?.image_url"
                        class="flex items-center gap-2 font-normal"
                    >
                        <Checkbox v-model="wordForm.remove_image" />
                        Remove the current picture
                    </Label>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="grid gap-2">
                            <Label for="target_sound">Target sound</Label>
                            <Input
                                id="target_sound"
                                v-model="wordForm.target_sound"
                                placeholder="s"
                                maxlength="10"
                                :aria-invalid="!!wordForm.errors.target_sound"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="word_position">Position</Label>
                            <Select v-model="wordForm.word_position">
                                <SelectTrigger
                                    id="word_position"
                                    class="w-full"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="noPosition"
                                        >—</SelectItem
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
                        <div class="grid gap-2">
                            <Label for="category">Category</Label>
                            <Input
                                id="category"
                                v-model="wordForm.category"
                                placeholder="haiwan"
                                maxlength="50"
                            />
                        </div>
                    </div>
                    <InputError :message="wordForm.errors.target_sound" />
                    <InputError :message="wordForm.errors.word_position" />
                    <InputError :message="wordForm.errors.category" />

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="isWordDialogOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="wordForm.processing">
                            <Spinner v-if="wordForm.processing" />
                            {{ editingItem ? 'Save changes' : 'Add word' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="itemToDelete !== null"
            @update:open="(open: boolean) => !open && (itemToDelete = null)"
        >
            <DialogContent v-if="itemToDelete">
                <Form
                    v-bind="destroy.form(itemToDelete.id)"
                    :options="{ preserveScroll: true }"
                    class="space-y-6"
                    @success="itemToDelete = null"
                    v-slot="{ processing }"
                >
                    <DialogHeader>
                        <DialogTitle
                            >Delete “{{ itemToDelete.word }}”?</DialogTitle
                        >
                        <DialogDescription>
                            Words used in an activity or with recorded answers
                            are kept.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="itemToDelete = null"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="processing"
                        >
                            Delete word
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
