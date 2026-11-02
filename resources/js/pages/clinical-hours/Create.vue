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
    mode: 'log_form',
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
    hours: 0,
    minutes: 0,
};
</script>

<template>
    <Head title="New log form" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            title="New clinical log form"
            description="Log each session like the paper KSP form, or switch to simple mode and key in your hours"
        />

        <ClinicalLogFormEditor
            :options="options"
            :initial-values="initialValues"
            :action="store()"
            submit-label="Save log form"
            remembers-mode
        />
    </div>
</template>
