<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    create,
    index,
    store,
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
                title: 'New log form',
                href: create(),
            },
        ],
    },
});

defineProps<{
    options: ClinicalLogFormOptions;
}>();

const today = new Date();

const initialValues: ClinicalLogFormInput = {
    year: '',
    semester: '',
    session_start_year:
        today.getMonth() >= 8 ? today.getFullYear() : today.getFullYear() - 1,
    days: [],
    placement: '',
    clinic_mode: '',
    placement_name: '',
    supervisor_name: '',
    practicum_type: '',
    hours_type: '',
    entries: [],
};
</script>

<template>
    <Head title="New log form" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            title="New clinical log form"
            description="Fill in one form per placement and hours type, just like the paper KSP log form"
        />

        <ClinicalLogFormEditor
            :options="options"
            :initial-values="initialValues"
            :action="store()"
            submit-label="Save log form"
        />
    </div>
</template>
