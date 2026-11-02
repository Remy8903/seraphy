<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { CloudOff, LoaderCircle, RefreshCw } from '@lucide/vue';
import { computed, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { useOfflineSync } from '@/composables/useOfflineSync';
import {
    deleteOtherOwnersData,
    isOfflineStorageSupported,
} from '@/lib/offlineStore';
import { offline } from '@/routes';

const page = usePage();
const { isOnline, unsyncedCount, isSyncing, syncError, sync, refreshCount } =
    useOfflineSync();

const sessionsLabel = computed(
    () =>
        `${unsyncedCount.value} ${unsyncedCount.value === 1 ? 'session' : 'sessions'}`,
);

onMounted(async () => {
    if (!isOfflineStorageSupported()) {
        return;
    }

    try {
        await deleteOtherOwnersData(page.props.auth.user.id);
        await refreshCount();
        await sync();
    } catch {
        // Storage may be blocked; the banner simply stays hidden.
    }
});
</script>

<template>
    <div
        v-if="!isOnline || unsyncedCount > 0"
        class="flex flex-wrap items-center gap-x-3 gap-y-1 border-b bg-amber-50 px-4 py-2 text-sm text-amber-950 dark:bg-amber-950/40 dark:text-amber-100"
        role="status"
    >
        <CloudOff class="size-4 shrink-0" />
        <span v-if="!isOnline">
            Offline — results will sync when you&rsquo;re back online<template
                v-if="unsyncedCount > 0"
            >
                · {{ sessionsLabel }} waiting</template
            >.
            <Link :href="offline()" class="underline underline-offset-4">
                Open offline play
            </Link>
        </span>
        <span v-else>
            {{ sessionsLabel }} played offline
            {{ unsyncedCount === 1 ? 'is' : 'are' }} waiting to sync.
            <template v-if="syncError">{{ syncError }}</template>
        </span>
        <Button
            v-if="isOnline"
            variant="outline"
            size="sm"
            class="ml-auto h-7"
            :disabled="isSyncing"
            @click="sync"
        >
            <LoaderCircle v-if="isSyncing" class="animate-spin" />
            <RefreshCw v-else />
            Sync now
        </Button>
    </div>
</template>
