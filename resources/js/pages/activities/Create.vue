<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    create,
    index,
    store,
} from '@/actions/App/Http/Controllers/ActivityController';
import Heading from '@/components/Heading.vue';
import ActivityBuilder from '@/components/therapy/ActivityBuilder.vue';
import type {
    ActivityBuilderOptions,
    ActivityInput,
    LibraryItem,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Activities', href: index() },
            { title: 'New activity', href: create() },
        ],
    },
});

defineProps<{
    options: ActivityBuilderOptions;
    library: LibraryItem[];
}>();

const initialValues: ActivityInput = {
    game_type: 'sound_drill',
    title: '',
    language: 'ms',
    settings: {
        trial_count: 20,
        shuffle: true,
        show_word_text: false,
        reward_every: 5,
        sticker_set: 'animals',
    },
    items: [],
    directions: [],
};
</script>

<template>
    <Head title="New activity" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            title="New activity"
            description="Pick a game, then choose the words to practise"
        />

        <ActivityBuilder
            :options="options"
            :library="library"
            :initial-values="initialValues"
            :action="store()"
            submit-label="Save activity"
        />
    </div>
</template>
