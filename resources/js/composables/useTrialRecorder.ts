import { useHttp } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { store as storeTrials } from '@/actions/App/Http/Controllers/TrialController';
import type { TrialAnswer } from '@/types';

type QueuedTrial = TrialAnswer & {
    client_uuid: string;
};

type TrialsResponse = {
    saved: string[];
    recorded_trials: number;
};

const retryDelayMs = 3000;
const maxBatchSize = 100;

/**
 * Create a random v4 UUID. Falls back to getRandomValues because
 * crypto.randomUUID only exists on HTTPS pages.
 */
export function randomUuid(): string {
    if (typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    const bytes = crypto.getRandomValues(new Uint8Array(16));
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = [...bytes].map((byte) => byte.toString(16).padStart(2, '0'));

    return `${hex.slice(0, 4).join('')}-${hex.slice(4, 6).join('')}-${hex.slice(6, 8).join('')}-${hex.slice(8, 10).join('')}-${hex.slice(10).join('')}`;
}

/**
 * Record answers for a play session in the background.
 *
 * Answers are queued (and kept in localStorage) until the server confirms
 * them, so a dropped connection or a page reload never loses an answer.
 * Re-recording an answer with the same UUID corrects it on the server.
 */
export function useTrialRecorder(
    sessionId: number,
    initialRecordedCount: number,
) {
    const storageKey = `therapy.pending-trials.${sessionId}`;
    const pending = ref(new Map<string, QueuedTrial>());
    const recordedCount = ref(initialRecordedCount);
    const hasConnectionProblem = ref(false);
    const rejectionMessage = ref<string | null>(null);

    const http = useHttp<{ trials: QueuedTrial[] }, TrialsResponse>({
        trials: [],
    });

    let retryTimer: ReturnType<typeof setTimeout> | null = null;

    function persist(): void {
        try {
            if (pending.value.size === 0) {
                localStorage.removeItem(storageKey);
            } else {
                localStorage.setItem(
                    storageKey,
                    JSON.stringify([...pending.value.values()]),
                );
            }
        } catch {
            // Storage may be unavailable; answers stay queued in memory.
        }
    }

    function restore(): void {
        try {
            const stored = localStorage.getItem(storageKey);

            if (stored) {
                for (const trial of JSON.parse(stored) as QueuedTrial[]) {
                    pending.value.set(trial.client_uuid, trial);
                }
            }
        } catch {
            // Ignore unreadable storage.
        }
    }

    function scheduleRetry(): void {
        if (retryTimer === null) {
            retryTimer = setTimeout(() => {
                retryTimer = null;
                void flush();
            }, retryDelayMs);
        }
    }

    /**
     * Send queued answers. Resolves true once nothing is left to send.
     */
    async function flush(): Promise<boolean> {
        if (pending.value.size === 0) {
            return true;
        }

        if (http.processing || rejectionMessage.value) {
            return false;
        }

        const batch = [...pending.value.values()].slice(0, maxBatchSize);
        http.trials = batch;

        try {
            const response = await http.post(storeTrials.url(sessionId));

            for (const sent of batch) {
                if (pending.value.get(sent.client_uuid) === sent) {
                    pending.value.delete(sent.client_uuid);
                }
            }

            recordedCount.value = response.recorded_trials;
            hasConnectionProblem.value = false;
            persist();

            return flush();
        } catch {
            if (http.hasErrors) {
                rejectionMessage.value =
                    Object.values(http.errors)[0] ??
                    'Some answers could not be saved.';
            } else {
                hasConnectionProblem.value = true;
                scheduleRetry();
            }

            return false;
        }
    }

    /**
     * Queue an answer and send it. Pass an existing UUID to correct that answer.
     */
    function record(answer: TrialAnswer, uuid: string = randomUuid()): string {
        pending.value.set(uuid, { ...answer, client_uuid: uuid });
        persist();
        void flush();

        return uuid;
    }

    function retryWhenOnline(): void {
        void flush();
    }

    onMounted(() => {
        restore();
        window.addEventListener('online', retryWhenOnline);
        void flush();
    });

    onBeforeUnmount(() => {
        window.removeEventListener('online', retryWhenOnline);

        if (retryTimer !== null) {
            clearTimeout(retryTimer);
        }
    });

    return {
        record,
        flush,
        recordedCount,
        pendingCount: computed(() => pending.value.size),
        isSaving: computed(() => http.processing),
        hasConnectionProblem,
        rejectionMessage,
    };
}
