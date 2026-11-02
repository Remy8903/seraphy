<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    index,
    show,
    update,
} from '@/actions/App/Http/Controllers/ClientController';
import Heading from '@/components/Heading.vue';
import ClientForm from '@/components/therapy/ClientForm.vue';
import type { ClientFormOptions, ClientInput } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Clients', href: index() },
            { title: 'Edit client', href: '#' },
        ],
    },
});

defineProps<{
    options: ClientFormOptions;
    client: ClientInput & { id: number };
}>();
</script>

<template>
    <Head title="Edit client" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            :title="`Edit ${client.reference_code}`"
            description="Keep the details limited to a reference and general clinical terms"
        />

        <ClientForm
            :options="options"
            :initial-values="client"
            :action="update(client.id)"
            :cancel-href="show(client.id).url"
            submit-label="Save changes"
        />
    </div>
</template>
