<script setup lang="ts">
import { ref } from 'vue';
import GamePlayer from '@/components/therapy/GamePlayer.vue';
import { randomUuid } from '@/composables/useTrialRecorder';
import { putOffline } from '@/lib/offlineStore';
import type {
    OfflineActivity,
    OfflineSession,
    OfflineTrial,
} from '@/lib/offlineStore';
import type { GameRecorder, GameStickerBoard, TrialAnswer } from '@/types';

const props = defineProps<{
    content: OfflineActivity;
    session: OfflineSession;
}>();

const emit = defineEmits<{
    pause: [];
    finished: [];
}>();

const trials = new Map<string, OfflineTrial>(
    props.session.trials.map((trial) => [trial.client_uuid, trial]),
);
const stickers = ref<string[]>([...props.session.stickers]);
let endedAt: string | null = null;

/** Writes run one after another, so the last write always holds the latest answers. */
let saving: Promise<void> = Promise.resolve();

function save(): Promise<void> {
    saving = saving
        .catch(() => {})
        .then(() =>
            putOffline<OfflineSession>('sessions', {
                ...props.session,
                ended_at: endedAt,
                trials: [...trials.values()],
                stickers: stickers.value,
            }),
        );

    return saving;
}

async function saved(): Promise<boolean> {
    try {
        await saving;

        return true;
    } catch {
        return false;
    }
}

const recorder: GameRecorder = {
    record: (answer: TrialAnswer, uuid: string = randomUuid()): string => {
        trials.set(uuid, { ...answer, client_uuid: uuid });
        void save();

        return uuid;
    },
    flush: saved,
    pendingCount: ref(0),
    hasConnectionProblem: ref(false),
    rejectionMessage: ref<string | null>(null),
};

const stickerBoard: GameStickerBoard = {
    stickers,
    add: (sticker: string): void => {
        stickers.value = [...stickers.value, sticker];
        void save();
    },
    save: saved,
};

async function finish(): Promise<void> {
    endedAt = new Date().toISOString();
    await save();
    emit('finished');
}
</script>

<template>
    <GamePlayer
        :activity="content.activity"
        :cards="content.cards"
        :directions="content.directions"
        :sticker-choices="content.stickerChoices"
        :recorded-trials="session.trials.length"
        :recorder="recorder"
        :sticker-board="stickerBoard"
        :finish="finish"
        saves-on-device
        @pause="emit('pause')"
    />
</template>
