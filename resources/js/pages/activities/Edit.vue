<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    index,
    update,
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
            { title: 'Edit activity', href: '#' },
        ],
    },
});

defineProps<{
    options: ActivityBuilderOptions;
    library: LibraryItem[];
    activity: ActivityInput & { id: number; background_url: string | null };
}>();
</script>

<template>
    <Head title="Edit activity" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            :title="`Edit ${activity.title}`"
            description="Changes apply to future sessions; past results are kept"
        />

        <ActivityBuilder
            :options="options"
            :library="library"
            :initial-values="activity"
            :background-url="activity.background_url"
            :action="update(activity.id)"
            submit-label="Save changes"
        />
    </div>
</template>
