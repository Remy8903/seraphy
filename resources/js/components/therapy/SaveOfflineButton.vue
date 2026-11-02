<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { CloudDownload, CloudOff, LoaderCircle } from '@lucide/vue';
import { onMounted, ref } from 'vue';
import OfflineActivityController from '@/actions/App/Http/Controllers/OfflineActivityController';
import { Button } from '@/components/ui/button';
import {
    getAllOffline,
    isOfflineStorageSupported,
    offlineActivityKey,
    pictureCacheName,
    putOffline,
} from '@/lib/offlineStore';
import type { OfflineActivity } from '@/lib/offlineStore';

const props = defineProps<{
    clientId: number;
    activityId: number;
    activityTitle: string;
}>();

const page = usePage();
const key = offlineActivityKey(props.clientId, props.activityId);
const status = ref<'idle' | 'saving' | 'saved' | 'failed'>('idle');

onMounted(async () => {
    if (!isOfflineStorageSupported()) {
        return;
    }

    try {
        const saved = await getAllOffline<OfflineActivity>('activities');

        if (saved.some((activity) => activity.key === key)) {
            status.value = 'saved';
        }
    } catch {
        // Storage may be blocked; the button still lets the clinician try.
    }
});

/**
 * Download the activity and its uploaded pictures onto this device, so it can be
 * played from the offline screen without internet. Saving again refreshes it.
 */
async function save(): Promise<void> {
    status.value = 'saving';

    try {
        const response = await fetch(
            OfflineActivityController.url([props.clientId, props.activityId]),
            {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        );

        if (!response.ok) {
            throw new Error(`Download failed with ${response.status}`);
        }

        const content = (await response.json()) as Omit<
            OfflineActivity,
            'key' | 'owner_id'
        >;

        // Word pictures and the background, so the game looks the same offline.
        const pictureUrls = [
            ...new Set(
                content.cards
                    .flatMap((card) => [card.word, card.pair])
                    .map((picture) => picture?.image_url)
                    .concat(content.activity.background_url)
                    .filter((url): url is string => !!url),
            ),
        ];

        if (pictureUrls.length && 'caches' in window) {
            const cache = await caches.open(pictureCacheName);
            await cache.addAll(pictureUrls);
        }

        await putOffline<OfflineActivity>('activities', {
            ...content,
            key,
            owner_id: page.props.auth.user.id,
        });
        status.value = 'saved';
    } catch {
        status.value = 'failed';
    }
}
</script>

<template>
    <Button
        v-if="isOfflineStorageSupported()"
        type="button"
        variant="ghost"
        size="icon"
        :disabled="status === 'saving'"
        :title="
            status === 'saved'
                ? 'Saved for offline play. Click to update it.'
                : status === 'failed'
                  ? 'Could not save for offline play. Try again.'
                  : 'Save for offline play'
        "
        :aria-label="`Save ${activityTitle} for offline play`"
        @click="save"
    >
        <LoaderCircle v-if="status === 'saving'" class="animate-spin" />
        <CloudOff v-else-if="status === 'saved'" class="text-emerald-600" />
        <CloudDownload
            v-else
            :class="{ 'text-destructive': status === 'failed' }"
        />
    </Button>
</template>
