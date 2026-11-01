<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    index,
    update,
} from '@/actions/App/Http/Controllers/ClinicalLogFormController';
import ClinicalLogFormEditor from '@/components/ClinicalLogFormEditor.vue';
import Heading from '@/components/Heading.vue';
import type { ClinicalLogFormInput, ClinicalLogFormOptions } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Clinical Hours',
                href: index(),
            },
            {
                title: 'Edit log form',
                href: '#',
            },
        ],
    },
});

defineProps<{
    options: ClinicalLogFormOptions;
    form: ClinicalLogFormInput & { id: number };
}>();
</script>

<template>
    <Head title="Edit log form" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            title="Edit clinical log form"
            description="Changes are reflected in your hours tracker and the downloaded PDF"
        />

        <ClinicalLogFormEditor
            :options="options"
            :initial-values="form"
            :action="update(form.id)"
            submit-label="Save changes"
        />
    </div>
</template>
