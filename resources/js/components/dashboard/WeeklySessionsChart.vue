<script setup lang="ts">
import { computed, ref } from 'vue';
import type { DashboardWeek } from '@/types';

const props = defineProps<{
    weeks: DashboardWeek[];
}>();

const chartHeight = 160;
const activeIndex = ref<number | null>(null);

/** A clean top for the axis: at least 4, rounded up to an even number. */
const axisMax = computed(() => {
    const highest = Math.max(0, ...props.weeks.map((week) => week.sessions));

    return Math.max(4, Math.ceil(highest / 2) * 2);
});

const ticks = computed(() => [axisMax.value, axisMax.value / 2, 0]);

function weekLabel(weekStart: string): string {
    return new Date(`${weekStart}T00:00:00`).toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
    });
}

function barHeight(sessions: number): number {
    return sessions === 0
        ? 0
        : Math.max(4, (sessions / axisMax.value) * chartHeight);
}
</script>

<template>
    <div>
        <div class="flex gap-3" aria-hidden="true">
            <div
                class="relative w-6 shrink-0 text-right text-xs text-muted-foreground tabular-nums"
                :style="{ height: `${chartHeight}px` }"
            >
                <span
                    v-for="tick in ticks"
                    :key="tick"
                    class="absolute right-0 -translate-y-1/2"
                    :style="{ top: `${(1 - tick / axisMax) * 100}%` }"
                >
                    {{ tick }}
                </span>
            </div>

            <div class="relative flex-1">
                <div
                    v-for="tick in ticks"
                    :key="tick"
                    class="absolute inset-x-0 border-t border-border"
                    :style="{ top: `${(1 - tick / axisMax) * chartHeight}px` }"
                />

                <div
                    class="relative grid items-end"
                    :style="{
                        height: `${chartHeight}px`,
                        gridTemplateColumns: `repeat(${weeks.length}, minmax(0, 1fr))`,
                    }"
                >
                    <div
                        v-for="(week, index) in weeks"
                        :key="week.week_start"
                        class="relative flex h-full items-end justify-center"
                        @mouseenter="activeIndex = index"
                        @mouseleave="activeIndex = null"
                    >
                        <span
                            v-if="
                                index === weeks.length - 1 && week.sessions > 0
                            "
                            class="absolute text-xs font-medium text-foreground tabular-nums"
                            :style="{
                                bottom: `${barHeight(week.sessions) + 4}px`,
                            }"
                        >
                            {{ week.sessions }}
                        </span>
                        <div
                            class="w-full max-w-6 rounded-t-[4px] transition-opacity"
                            :class="
                                index === weeks.length - 1
                                    ? 'bg-brand-chart'
                                    : 'bg-brand-chart/70'
                            "
                            :style="{
                                height: `${barHeight(week.sessions)}px`,
                                opacity:
                                    activeIndex === null ||
                                    activeIndex === index
                                        ? 1
                                        : 0.45,
                            }"
                        />
                        <div
                            v-if="activeIndex === index"
                            class="pointer-events-none absolute bottom-full z-10 mb-1 rounded-md border bg-popover px-2 py-1 text-xs whitespace-nowrap text-popover-foreground shadow-md"
                        >
                            <p class="text-muted-foreground">
                                Week of {{ weekLabel(week.week_start) }}
                            </p>
                            <p class="font-semibold tabular-nums">
                                {{ week.sessions }}
                                {{
                                    week.sessions === 1 ? 'session' : 'sessions'
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-2 grid text-center text-xs text-muted-foreground"
                    :style="{
                        gridTemplateColumns: `repeat(${weeks.length}, minmax(0, 1fr))`,
                    }"
                >
                    <span
                        v-for="(week, index) in weeks"
                        :key="week.week_start"
                        :class="{
                            'max-sm:invisible':
                                index % 2 === 1 && index !== weeks.length - 1,
                        }"
                    >
                        {{
                            index === weeks.length - 1
                                ? 'This week'
                                : weekLabel(week.week_start)
                        }}
                    </span>
                </div>
            </div>
        </div>

        <table class="sr-only">
            <caption>
                Finished sessions per week
            </caption>
            <thead>
                <tr>
                    <th scope="col">Week of</th>
                    <th scope="col">Sessions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="week in weeks" :key="week.week_start">
                    <td>{{ weekLabel(week.week_start) }}</td>
                    <td>{{ week.sessions }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
