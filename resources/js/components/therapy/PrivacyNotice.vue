<script setup lang="ts">
import { ShieldAlert } from '@lucide/vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

withDefaults(
    defineProps<{
        context?: 'client' | 'upload';
    }>(),
    {
        context: 'client',
    },
);
</script>

<template>
    <Alert
        class="border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100"
    >
        <ShieldAlert />
        <AlertTitle>
            {{
                context === 'upload'
                    ? 'Do not upload photos of patients'
                    : 'Do not enter real patient details'
            }}
        </AlertTitle>
        <AlertDescription class="text-amber-900/90 dark:text-amber-100/90">
            <template v-if="context === 'upload'">
                Use pictures of objects, animals or drawings only. Never upload
                photos of a client, their family or their records.
            </template>
            <template v-else>
                Use a reference only, such as the RN no. or your own code like
                C-01. No names, IC numbers, phone numbers, addresses, emails or
                photos of patients.
            </template>
        </AlertDescription>
    </Alert>
</template>
