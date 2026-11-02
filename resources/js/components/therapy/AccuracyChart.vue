<script setup lang="ts">
import { computed } from 'vue';
import type { SessionAccuracy } from '@/types';

const props = defineProps<{
    sessions: SessionAccuracy[];
    label: string;
}>();

const width = 320;
const height = 140;
const padding = { top: 12, right: 12, bottom: 22, left: 32 };
const plotWidth = width - padding.left - padding.right;
const plotHeight = height - padding.top - padding.bottom;
const gridLines = [0, 50, 100];

function yFor(accuracy: number): number {
    return padding.top + plotHeight - (accuracy / 100) * plotHeight;
}

const points = computed(() =>
    props.sessions.map((session, index) => ({
        ...session,
        x:
            padding.left +
            (props.sessions.length === 1
                ? plotWidth / 2
                : (index / (props.sessions.length - 1)) * plotWidth),
        y: yFor(session.accuracy),
    })),
);

const path = computed(() =>
    points.value.map((point) => `${point.x},${point.y}`).join(' '),
);

function shortDate(date: string): string {
    return new Date(date).toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
    });
}
</script>

<template>
    <figure class="w-full">
        <svg
            :viewBox="`0 0 ${width} ${height}`"
            class="w-full text-primary"
            role="img"
            :aria-label="`${label}: accuracy over ${sessions.length} sessions`"
        >
            <g v-for="line in gridLines" :key="line">
                <line
                    :x1="padding.left"
                    :x2="width - padding.right"
                    :y1="yFor(line)"
                    :y2="yFor(line)"
                    class="stroke-border"
                    stroke-dasharray="3 3"
                />
                <text
                    :x="padding.left - 6"
                    :y="yFor(line) + 3"
                    text-anchor="end"
                    class="fill-muted-foreground text-[9px]"
                >
                    {{ line }}%
                </text>
            </g>

            <polyline
                v-if="points.length > 1"
                :points="path"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linejoin="round"
                stroke-linecap="round"
            />

            <g v-for="point in points" :key="point.id">
                <circle
                    :cx="point.x"
                    :cy="point.y"
                    r="4"
                    fill="currentColor"
                    class="stroke-background"
                    stroke-width="2"
                >
                    <title>
                        {{ shortDate(point.date) }}: {{ point.accuracy }}% ({{
                            point.correct
                        }}/{{ point.total }} correct)
                    </title>
                </circle>
            </g>

            <text
                v-if="points.length > 0"
                :x="points[0].x"
                :y="height - 6"
                :text-anchor="points.length === 1 ? 'middle' : 'start'"
                class="fill-muted-foreground text-[9px]"
            >
                {{ shortDate(points[0].date) }}
            </text>
            <text
                v-if="points.length > 1"
                :x="points[points.length - 1].x"
                :y="height - 6"
                text-anchor="end"
                class="fill-muted-foreground text-[9px]"
            >
                {{ shortDate(points[points.length - 1].date) }}
            </text>
        </svg>
    </figure>
</template>
