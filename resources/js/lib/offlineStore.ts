import type {
    GameActivity,
    GameCard,
    GameDirection,
    TrialAnswer,
} from '@/types';

/**
 * On-device storage for offline play, using IndexedDB.
 *
 * Only reference codes, activity content and results are stored, the same as
 * the play screen shows. Signing out clears everything (see clearOfflineData).
 */

const databaseName = 'theratrack-offline';
const databaseVersion = 1;

/** Cache the service worker keeps uploaded pictures in. */
export const pictureCacheName = 'therapy-pictures';

/** An activity saved on the device for a client, ready to play without internet. */
export type OfflineActivity = {
    /** `${client.id}:${activity.id}` */
    key: string;
    owner_id: number;
    client: { id: number; reference_code: string };
    activity: GameActivity & { id: number };
    cards: GameCard[];
    directions: GameDirection[];
    stickerChoices: string[];
    downloaded_at: string;
};

export type OfflineTrial = TrialAnswer & { client_uuid: string };

/** A session played offline, kept until it is synced. */
export type OfflineSession = {
    client_uuid: string;
    owner_id: number;
    activity_key: string;
    client_id: number;
    client_reference: string;
    activity_id: number;
    activity_title: string;
    started_at: string;
    /** Set when the session is ended; only ended sessions are synced. */
    ended_at: string | null;
    trials: OfflineTrial[];
    stickers: string[];
};

type StoreName = 'activities' | 'sessions';

export function offlineActivityKey(
    clientId: number,
    activityId: number,
): string {
    return `${clientId}:${activityId}`;
}

export function isOfflineStorageSupported(): boolean {
    return typeof window !== 'undefined' && 'indexedDB' in window;
}

let database: Promise<IDBDatabase> | null = null;

function openDatabase(): Promise<IDBDatabase> {
    database ??= new Promise((resolve, reject) => {
        const request = indexedDB.open(databaseName, databaseVersion);

        request.onupgradeneeded = () => {
            request.result.createObjectStore('activities', { keyPath: 'key' });
            request.result.createObjectStore('sessions', {
                keyPath: 'client_uuid',
            });
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => {
            database = null;
            reject(request.error);
        };
    });

    return database;
}

async function run<T>(
    store: StoreName,
    mode: IDBTransactionMode,
    action: (objectStore: IDBObjectStore) => IDBRequest<T>,
): Promise<T> {
    const db = await openDatabase();

    return new Promise((resolve, reject) => {
        const transaction = db.transaction(store, mode);
        const request = action(transaction.objectStore(store));

        transaction.oncomplete = () => resolve(request.result);
        transaction.onerror = () => reject(transaction.error);
        transaction.onabort = () => reject(transaction.error);
    });
}

/** Strip Vue proxies so the value can be stored. */
function plain<T>(value: T): T {
    return JSON.parse(JSON.stringify(value)) as T;
}

export function getAllOffline<T>(store: StoreName): Promise<T[]> {
    return run<T[]>(store, 'readonly', (objectStore) => objectStore.getAll());
}

export async function putOffline<T>(store: StoreName, value: T): Promise<void> {
    await run(store, 'readwrite', (objectStore) =>
        objectStore.put(plain(value)),
    );
}

export async function deleteOffline(
    store: StoreName,
    key: string,
): Promise<void> {
    await run(store, 'readwrite', (objectStore) => objectStore.delete(key));
}

/** Remove anything saved by another clinician who used this device. */
export async function deleteOtherOwnersData(ownerId: number): Promise<void> {
    for (const store of ['activities', 'sessions'] as const) {
        const records = await getAllOffline<
            { owner_id: number } & Record<string, unknown>
        >(store);

        for (const record of records) {
            if (record.owner_id !== ownerId) {
                await deleteOffline(
                    store,
                    String(
                        store === 'activities'
                            ? record.key
                            : record.client_uuid,
                    ),
                );
            }
        }
    }
}

/** Delete every offline activity, session and cached picture from the device. */
export async function clearOfflineData(): Promise<void> {
    if (isOfflineStorageSupported()) {
        await run('activities', 'readwrite', (objectStore) =>
            objectStore.clear(),
        );
        await run('sessions', 'readwrite', (objectStore) =>
            objectStore.clear(),
        );
    }

    if ('caches' in window) {
        await caches.delete(pictureCacheName);
    }
}
