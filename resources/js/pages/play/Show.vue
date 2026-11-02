<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { show as showClient } from '@/actions/App/Http/Controllers/ClientController';
import { finish } from '@/actions/App/Http/Controllers/PlaySessionController';
import GamePlayer from '@/components/therapy/GamePlayer.vue';
import { useStickerBoard } from '@/composables/useStickerBoard';
import { useTrialRecorder } from '@/composables/useTrialRecorder';
import type { GameActivity, GameCard, GameDirection } from '@/types';

const props = defineProps<{
    session: {
        id: number;
        client_id: number;
        recorded_trials: number;
        stickers: string[];
    };
    activity: GameActivity;
    cards: GameCard[];
    directions: GameDirection[];
    stickerChoices: string[];
}>();

const recorder = useTrialRecorder(
    props.session.id,
    props.session.recorded_trials,
);
const stickerBoard = useStickerBoard(props.session.id, props.session.stickers);

function finishSession(): Promise<void> {
    return new Promise((resolve) => {
        router.patch(
            finish.url(props.session.id),
            {},
            { onFinish: () => resolve() },
        );
    });
}

function pause(): void {
    router.visit(showClient(props.session.client_id));
}
</script>

<template>
    <Head :title="`Playing: ${activity.title}`" />

    <GamePlayer
        :activity="activity"
        :cards="cards"
        :directions="directions"
        :sticker-choices="stickerChoices"
        :recorded-trials="session.recorded_trials"
        :recorder="recorder"
        :sticker-board="stickerBoard"
        :finish="finishSession"
        @pause="pause"
    />
</template>
