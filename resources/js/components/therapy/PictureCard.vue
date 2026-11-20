<script setup lang="ts">
import { computed } from 'vue';
import TherapyIllustration from '@/components/therapy/TherapyIllustration.vue';
import { resolveIllustration } from '@/lib/therapyIllustrations';
import { cn } from '@/lib/utils';
import type { Picture } from '@/types';

const props = withDefaults(
    defineProps<{
        picture: Picture;
        size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
        showWord?: boolean;
    }>(),
    {
        size: 'md',
        showWord: false,
    },
);

const sizeClasses = computed(
    () =>
        ({
            xs: { box: 'w-6', word: 'text-xs' },
            sm: { box: 'w-10', word: 'text-xs' },
            md: { box: 'w-16', word: 'text-sm' },
            lg: {
                box: 'w-40 sm:w-48',
                word: 'text-2xl',
            },
            xl: {
                box: 'w-56 sm:w-72',
                word: 'text-3xl sm:text-4xl',
            },
        })[props.size],
);
const needsPicture = computed(
    () =>
        !props.picture.image_url &&
        !resolveIllustration(props.picture.emoji, props.picture.word).isKnown,
);
</script>

<template>
    <figure class="flex max-w-full min-w-0 flex-col items-center gap-2">
        <div
            :class="
                cn(
                    'flex aspect-square max-w-full items-center justify-center overflow-hidden rounded-xl',
                    sizeClasses.box,
                )
            "
        >
            <img
                v-if="picture.image_url"
                :src="picture.image_url"
                :alt="picture.word"
                class="size-full object-contain"
            />
            <TherapyIllustration
                v-else
                :symbol="picture.emoji"
                :word="picture.word"
                class="size-full object-contain"
            />
        </div>
        <figcaption
            v-if="needsPicture && !showWord"
            class="text-center text-xs text-muted-foreground"
        >
            Picture needed
        </figcaption>
        <figcaption
            v-if="showWord"
            :class="cn('font-semibold tracking-wide', sizeClasses.word)"
        >
            {{ picture.word }}
        </figcaption>
    </figure>
</template>
