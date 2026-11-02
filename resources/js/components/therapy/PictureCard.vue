<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { Picture } from '@/types';

const props = withDefaults(
    defineProps<{
        picture: Picture;
        size?: 'sm' | 'md' | 'lg' | 'xl';
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
            sm: { box: 'size-10', emoji: 'text-2xl', word: 'text-xs' },
            md: { box: 'size-16', emoji: 'text-4xl', word: 'text-sm' },
            lg: {
                box: 'size-40 sm:size-48',
                emoji: 'text-8xl',
                word: 'text-2xl',
            },
            xl: {
                box: 'size-56 sm:size-72',
                emoji: 'text-[8rem] sm:text-[10rem]',
                word: 'text-3xl sm:text-4xl',
            },
        })[props.size],
);
</script>

<template>
    <figure class="flex flex-col items-center gap-2">
        <div
            :class="
                cn(
                    'flex items-center justify-center overflow-hidden rounded-xl',
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
            <span
                v-else
                :class="cn('leading-none select-none', sizeClasses.emoji)"
                role="img"
                :aria-label="picture.word"
            >
                {{ picture.emoji }}
            </span>
        </div>
        <figcaption
            v-if="showWord"
            :class="cn('font-semibold tracking-wide', sizeClasses.word)"
        >
            {{ picture.word }}
        </figcaption>
    </figure>
</template>
