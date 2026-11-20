<script setup lang="ts">
import { computed } from 'vue';
import { resolveIllustration } from '@/lib/therapyIllustrations';

const props = withDefaults(
    defineProps<{
        symbol?: string | null;
        word?: string;
        title?: string;
    }>(),
    { symbol: null, word: '', title: undefined },
);

const illustration = computed(() =>
    resolveIllustration(props.symbol, props.word),
);
</script>

<template>
    <img
        :src="illustration.source"
        :alt="
            title ??
            (illustration.isKnown
                ? word || illustration.label
                : 'Picture needed: upload a matching image')
        "
        :data-illustration="illustration.key"
        class="select-none"
        draggable="false"
    />
</template>
