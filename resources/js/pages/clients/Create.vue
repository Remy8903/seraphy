<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    create,
    index,
    store,
} from '@/actions/App/Http/Controllers/ClientController';
import Heading from '@/components/Heading.vue';
import ClientForm from '@/components/therapy/ClientForm.vue';
import type { ClientFormOptions, ClientInput } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Clients', href: index() },
            { title: 'New client', href: create() },
        ],
    },
});

defineProps<{
    options: ClientFormOptions;
}>();

const initialValues: ClientInput = {
    reference_code: '',
    age_group: 'child_adolescent',
    language: 'ms',
    diagnosis: '',
    notes: '',
};
</script>

<template>
    <Head title="New client" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            title="New client"
            description="Add a client by reference so you can assign therapy games and track progress"
        />

        <ClientForm
            :options="options"
            :initial-values="initialValues"
            :action="store()"
            :cancel-href="index().url"
            submit-label="Add client"
            requires-confirmation
        />
    </div>
</template>
