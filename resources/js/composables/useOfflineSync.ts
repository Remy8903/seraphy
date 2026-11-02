import { ref } from 'vue';
import PlaySessionSyncController from '@/actions/App/Http/Controllers/PlaySessionSyncController';
import {
    deleteOffline,
    getAllOffline,
    isOfflineStorageSupported,
} from '@/lib/offlineStore';
import type { OfflineSession } from '@/lib/offlineStore';

const batchSize = 20;

const isOnline = ref(
    typeof navigator === 'undefined' ? true : navigator.onLine,
);
/** Sessions kept on this device that are not on the server yet, ended or not. */
const unsyncedCount = ref(0);
const isSyncing = ref(false);
const syncError = ref<string | null>(null);
let isListening = false;

function xsrfToken(): string {
    const cookie = document.cookie
        .split('; ')
        .find((entry) => entry.startsWith('XSRF-TOKEN='));

    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
}

async function refreshCount(): Promise<void> {
    if (!isOfflineStorageSupported()) {
        return;
    }

    unsyncedCount.value = (
        await getAllOffline<OfflineSession>('sessions')
    ).length;
}

/**
 * Upload every ended offline session, oldest first, and remove each one from
 * the device once the server has it. Resolves true when nothing is left to sync.
 */
async function sync(): Promise<boolean> {
    if (!isOfflineStorageSupported() || isSyncing.value || !navigator.onLine) {
        return false;
    }

    isSyncing.value = true;
    syncError.value = null;

    try {
        const sessions = (await getAllOffline<OfflineSession>('sessions'))
            .filter((session) => session.ended_at !== null)
            .sort((first, second) =>
                first.started_at.localeCompare(second.started_at),
            );

        // A session ended without any answers is discarded, as it is online.
        for (const empty of sessions.filter(
            (session) => session.trials.length === 0,
        )) {
            await deleteOffline('sessions', empty.client_uuid);
        }

        const withAnswers = sessions.filter(
            (session) => session.trials.length > 0,
        );

        for (let start = 0; start < withAnswers.length; start += batchSize) {
            const batch = withAnswers.slice(start, start + batchSize);
            const response = await fetch(PlaySessionSyncController.url(), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                body: JSON.stringify({
                    sessions: batch.map((session) => ({
                        client_uuid: session.client_uuid,
                        client_id: session.client_id,
                        activity_id: session.activity_id,
                        started_at: session.started_at,
                        ended_at: session.ended_at,
                        stickers: session.stickers,
                        trials: session.trials,
                    })),
                }),
            });

            if (response.status === 401 || response.status === 419) {
                syncError.value =
                    'Sign in again to sync the sessions played offline.';

                return false;
            }

            if (!response.ok) {
                const body = (await response.json().catch(() => null)) as {
                    errors?: Record<string, string[]>;
                } | null;
                syncError.value =
                    Object.values(body?.errors ?? {})[0]?.[0] ??
                    'Some offline sessions could not be synced.';

                return false;
            }

            const { synced } = (await response.json()) as { synced: string[] };

            for (const clientUuid of synced) {
                await deleteOffline('sessions', clientUuid);
            }
        }

        return true;
    } catch {
        return false;
    } finally {
        isSyncing.value = false;
        await refreshCount();
    }
}

function listen(): void {
    if (isListening || typeof window === 'undefined') {
        return;
    }

    isListening = true;
    window.addEventListener('online', () => {
        isOnline.value = true;
        void sync();
    });
    window.addEventListener('offline', () => {
        isOnline.value = false;
    });
}

/**
 * Connection status and syncing for sessions played offline. State is shared
 * by every component that uses it, and syncing starts again whenever the
 * device comes back online.
 */
export function useOfflineSync() {
    listen();

    return {
        isOnline,
        unsyncedCount,
        isSyncing,
        syncError,
        sync,
        refreshCount,
    };
}
