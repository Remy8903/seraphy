<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CloudOff,
    LoaderCircle,
    Play,
    RefreshCw,
    ShieldCheck,
    Trash2,
    Wifi,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import OfflineGame from '@/components/therapy/OfflineGame.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useOfflineSync } from '@/composables/useOfflineSync';
import { randomUuid } from '@/composables/useTrialRecorder';
import {
    deleteOffline,
    getAllOffline,
    isOfflineStorageSupported,
    putOffline,
} from '@/lib/offlineStore';
import type { OfflineActivity, OfflineSession } from '@/lib/offlineStore';

const { isOnline, unsyncedCount, isSyncing, syncError, sync, refreshCount } =
    useOfflineSync();

const activities = ref<OfflineActivity[]>([]);
const sessions = ref<OfflineSession[]>([]);
const isLoading = ref(true);
const loadError = ref<string | null>(null);
const playing = ref<{
    content: OfflineActivity;
    session: OfflineSession;
} | null>(null);
const notice = ref<string | null>(null);

/** Saved activities grouped by client reference code. */
const clients = computed(() => {
    const groups = new Map<string, OfflineActivity[]>();

    for (const activity of activities.value) {
        groups.set(activity.client.reference_code, [
            ...(groups.get(activity.client.reference_code) ?? []),
            activity,
        ]);
    }

    return [...groups]
        .sort(([first], [second]) => first.localeCompare(second))
        .map(([reference, saved]) => ({
            reference,
            activities: saved.sort((first, second) =>
                first.activity.title.localeCompare(second.activity.title),
            ),
        }));
});

const unfinishedSessions = computed(() =>
    sessions.value.filter((session) => session.ended_at === null),
);

async function load(): Promise<void> {
    if (!isOfflineStorageSupported()) {
        loadError.value =
            'This browser cannot store activities for offline play.';
        isLoading.value = false;

        return;
    }

    try {
        activities.value = await getAllOffline<OfflineActivity>('activities');
        sessions.value = await getAllOffline<OfflineSession>('sessions');
        await refreshCount();
    } catch {
        loadError.value =
            'Offline storage is blocked in this browser, so nothing can be played offline.';
    } finally {
        isLoading.value = false;
    }
}

onMounted(async () => {
    await load();
    await syncAndReload();
});

async function syncAndReload(): Promise<void> {
    await sync();
    await load();
}

async function start(content: OfflineActivity): Promise<void> {
    const session: OfflineSession = {
        client_uuid: randomUuid(),
        owner_id: content.owner_id,
        activity_key: content.key,
        client_id: content.client.id,
        client_reference: content.client.reference_code,
        activity_id: content.activity.id,
        activity_title: content.activity.title,
        started_at: new Date().toISOString(),
        ended_at: null,
        trials: [],
        stickers: [],
    };

    await putOffline<OfflineSession>('sessions', session);
    notice.value = null;
    playing.value = { content, session };
}

function resume(session: OfflineSession): void {
    const content = activities.value.find(
        (activity) => activity.key === session.activity_key,
    );

    if (content) {
        notice.value = null;
        playing.value = { content, session };
    }
}

async function discard(session: OfflineSession): Promise<void> {
    await deleteOffline('sessions', session.client_uuid);
    await load();
}

async function removeActivity(content: OfflineActivity): Promise<void> {
    await deleteOffline('activities', content.key);
    await load();
}

async function paused(): Promise<void> {
    playing.value = null;
    await load();
}

async function finished(): Promise<void> {
    playing.value = null;
    const isSynced = await sync();
    await load();
    notice.value =
        isSynced && unsyncedCount.value === 0
            ? 'Session saved and synced.'
            : 'Session saved on this device. It will sync when you are back online and signed in.';
}

function formatTime(iso: string): string {
    return new Date(iso).toLocaleString(undefined, {
        day: 'numeric',
        month: 'short',
        hour: 'numeric',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head title="Offline play" />

    <OfflineGame
        v-if="playing"
        :key="playing.session.client_uuid"
        :content="playing.content"
        :session="playing.session"
        @pause="paused"
        @finished="finished"
    />

    <div v-else class="mx-auto flex max-w-3xl flex-col gap-6 p-4 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold">Offline play</h1>
                <p class="text-sm text-muted-foreground">
                    Play activities you saved on this device, with or without
                    internet.
                </p>
            </div>
            <Button v-if="isOnline" variant="outline" as-child>
                <a href="/clients">
                    <ArrowLeft />
                    Back to clients
                </a>
            </Button>
        </div>

        <div
            class="flex flex-wrap items-center gap-x-3 gap-y-2 rounded-lg border px-4 py-3 text-sm"
            :class="
                isOnline
                    ? ''
                    : 'border-amber-300 bg-amber-50 text-amber-950 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100'
            "
            role="status"
        >
            <Wifi v-if="isOnline" class="size-4 text-emerald-600" />
            <CloudOff v-else class="size-4" />
            <span>
                <template v-if="isOnline">Online.</template>
                <template v-else>
                    Offline — results will sync when you&rsquo;re back online.
                </template>
                {{ unsyncedCount }}
                {{ unsyncedCount === 1 ? 'session' : 'sessions' }} not synced
                yet.
            </span>
            <Button
                v-if="isOnline && unsyncedCount > 0"
                size="sm"
                variant="outline"
                class="ml-auto"
                :disabled="isSyncing"
                @click="syncAndReload"
            >
                <LoaderCircle v-if="isSyncing" class="animate-spin" />
                <RefreshCw v-else />
                Sync now
            </Button>
            <p v-if="syncError" class="basis-full text-destructive">
                {{ syncError }}
            </p>
        </div>

        <p
            v-if="notice"
            class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100"
            role="status"
        >
            {{ notice }}
        </p>

        <p
            v-if="loadError"
            class="rounded-md bg-destructive/10 px-4 py-3 text-sm text-destructive"
            role="alert"
        >
            {{ loadError }}
        </p>

        <Card v-if="unfinishedSessions.length">
            <CardHeader>
                <CardTitle>Not finished</CardTitle>
                <CardDescription>
                    Sessions sync once you end them
                </CardDescription>
            </CardHeader>
            <CardContent>
                <ul class="divide-y rounded-md border">
                    <li
                        v-for="session in unfinishedSessions"
                        :key="session.client_uuid"
                        class="flex items-center gap-3 p-3"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">
                                {{ session.client_reference }} ·
                                {{ session.activity_title }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Started {{ formatTime(session.started_at) }} ·
                                {{ session.trials.length }} answers
                            </p>
                        </div>
                        <Button
                            size="sm"
                            :disabled="
                                !activities.some(
                                    (activity) =>
                                        activity.key === session.activity_key,
                                )
                            "
                            @click="resume(session)"
                        >
                            <Play />
                            Resume
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            :aria-label="`Discard the unfinished session for ${session.client_reference}`"
                            @click="discard(session)"
                        >
                            <Trash2 />
                        </Button>
                    </li>
                </ul>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Saved activities</CardTitle>
                <CardDescription>
                    Save more from a client&rsquo;s page while online, using the
                    cloud button next to an assigned activity.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <p v-if="isLoading" class="text-sm text-muted-foreground">
                    Loading…
                </p>
                <p
                    v-else-if="clients.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No activities are saved on this device yet.
                </p>
                <div
                    v-for="client in clients"
                    :key="client.reference"
                    class="space-y-2"
                >
                    <p class="font-medium">{{ client.reference }}</p>
                    <ul class="divide-y rounded-md border">
                        <li
                            v-for="saved in client.activities"
                            :key="saved.key"
                            class="flex items-center gap-3 p-3"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">
                                    {{ saved.activity.title }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Saved {{ formatTime(saved.downloaded_at) }}
                                </p>
                            </div>
                            <Button size="sm" @click="start(saved)">
                                <Play />
                                Start
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                :aria-label="`Remove ${saved.activity.title} from this device`"
                                @click="removeActivity(saved)"
                            >
                                <Trash2 />
                            </Button>
                        </li>
                    </ul>
                </div>
            </CardContent>
        </Card>

        <p class="flex items-start gap-2 text-xs text-muted-foreground">
            <ShieldCheck class="mt-0.5 size-4 shrink-0" />
            Only client reference codes, activity pictures and results are
            stored on this device. Signing out removes them.
        </p>
    </div>
</template>
