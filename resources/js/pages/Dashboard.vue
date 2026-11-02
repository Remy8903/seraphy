<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpen,
    CircleCheck,
    Clock,
    CloudOff,
    Gamepad2,
    Plus,
    Target,
    Trophy,
    Users,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import ActivityController from '@/actions/App/Http/Controllers/ActivityController';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import ClinicalLogFormController from '@/actions/App/Http/Controllers/ClinicalLogFormController';
import ContentItemController from '@/actions/App/Http/Controllers/ContentItemController';
import PlaySessionController from '@/actions/App/Http/Controllers/PlaySessionController';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import WeeklySessionsChart from '@/components/dashboard/WeeklySessionsChart.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useOfflineSync } from '@/composables/useOfflineSync';
import { dashboard, offline } from '@/routes';
import type { DashboardProps } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const props = defineProps<DashboardProps>();

const page = usePage();
const { unsyncedCount } = useOfflineSync();

const firstName = computed(
    () => page.props.auth.user.name.trim().split(/\s+/)[0] ?? '',
);

const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Good morning';
    }

    return hour < 18 ? 'Good afternoon' : 'Good evening';
});

const today = new Date().toLocaleDateString(undefined, {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

function toHours(minutes: number): string {
    const value = minutes / 60;

    return Number.isInteger(value) ? String(value) : value.toFixed(1);
}

const loggedMinutes = computed(() =>
    props.hours.categories.reduce(
        (total, category) => total + category.completed_minutes,
        0,
    ),
);

type FeatureTile = {
    title: string;
    href: ReturnType<typeof dashboard>;
    icon: Component;
    value: string;
    detail: string;
    /** Gradient for the icon chip, following the logo from violet to cyan. */
    tint: string;
};

const features = computed<FeatureTile[]>(() => [
    {
        title: 'Clinical hours',
        href: ClinicalLogFormController.index(),
        icon: Clock,
        value: `${toHours(loggedMinutes.value)} h`,
        detail: `logged in ${props.hours.forms_count} ${props.hours.forms_count === 1 ? 'form' : 'forms'}`,
        tint: 'from-brand-violet to-brand-indigo',
    },
    {
        title: 'Clients',
        href: ClientController.index(),
        icon: Users,
        value: String(props.counts.active_clients),
        detail:
            props.counts.active_clients === 1
                ? 'active client'
                : 'active clients',
        tint: 'from-brand-indigo to-brand-blue',
    },
    {
        title: 'Activities',
        href: ActivityController.index(),
        icon: Gamepad2,
        value: String(props.counts.activities),
        detail: props.counts.activities === 1 ? 'game set up' : 'games set up',
        tint: 'from-brand-blue to-brand-cyan',
    },
    {
        title: 'Word library',
        href: ContentItemController.index(),
        icon: BookOpen,
        value: String(props.counts.words),
        detail: `pictures, ${props.counts.own_words} of your own`,
        tint: 'from-brand-violet to-brand-blue',
    },
    {
        title: 'Offline play',
        href: offline(),
        icon: CloudOff,
        value: String(unsyncedCount.value),
        detail:
            unsyncedCount.value === 0
                ? 'unsynced sessions'
                : unsyncedCount.value === 1
                  ? 'session waiting to sync'
                  : 'sessions waiting to sync',
        tint: 'from-brand-indigo to-brand-cyan',
    },
]);

function formatSessionTime(iso: string): string {
    return new Date(iso).toLocaleString(undefined, {
        day: 'numeric',
        month: 'short',
        hour: 'numeric',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <section
            class="relative isolate overflow-hidden rounded-2xl bg-linear-to-br from-brand-violet via-brand-indigo to-brand-blue p-6 text-white shadow-sm sm:p-8"
        >
            <div
                class="absolute -top-16 -right-10 -z-10 size-64 rounded-full bg-brand-cyan/40 blur-3xl"
                aria-hidden="true"
            />
            <div
                class="absolute -bottom-24 left-1/3 -z-10 size-72 rounded-full bg-brand-violet/50 blur-3xl"
                aria-hidden="true"
            />
            <div
                class="absolute top-6 right-8 -z-10 hidden gap-3 sm:flex"
                aria-hidden="true"
            >
                <span
                    class="h-10 w-2.5 rotate-[35deg] rounded-full bg-white/25"
                />
                <span
                    class="mt-5 h-10 w-2.5 rotate-[55deg] rounded-full bg-white/20"
                />
            </div>

            <div
                class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-white/90 p-1.5 shadow-lg ring-1 ring-white/40"
                    >
                        <AppLogoIcon class="size-full rounded-xl" />
                    </div>
                    <div>
                        <p class="text-sm text-white/75">{{ today }}</p>
                        <h1 class="text-2xl font-semibold sm:text-3xl">
                            {{ greeting }}, {{ firstName }}
                        </h1>
                        <p class="mt-1 text-sm text-white/80">
                            <template v-if="sessions.this_week > 0">
                                {{ sessions.this_week }}
                                {{
                                    sessions.this_week === 1
                                        ? 'session'
                                        : 'sessions'
                                }}
                                played this week. Keep it up!
                            </template>
                            <template v-else>
                                Here&rsquo;s everything in TheraTrack at a
                                glance.
                            </template>
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button
                        as-child
                        class="bg-white text-brand-indigo shadow-sm hover:bg-white/90 hover:text-brand-indigo"
                    >
                        <Link :href="ClinicalLogFormController.create()">
                            <Plus />
                            Log hours
                        </Link>
                    </Button>
                    <Button
                        as-child
                        variant="ghost"
                        class="bg-white/15 text-white hover:bg-white/25 hover:text-white"
                    >
                        <Link :href="ClientController.create()">
                            <Users />
                            Add client
                        </Link>
                    </Button>
                    <Button
                        as-child
                        variant="ghost"
                        class="bg-white/15 text-white hover:bg-white/25 hover:text-white"
                    >
                        <Link :href="ActivityController.create()">
                            <Gamepad2 />
                            New activity
                        </Link>
                    </Button>
                </div>
            </div>
        </section>

        <section
            aria-label="Features"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5"
        >
            <Link
                v-for="feature in features"
                :key="feature.title"
                :href="feature.href"
                class="group flex flex-col gap-4 rounded-xl border bg-card p-4 transition-all hover:-translate-y-0.5 hover:border-brand-indigo/40 hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="flex size-10 items-center justify-center rounded-lg bg-linear-to-br text-white shadow-sm"
                        :class="feature.tint"
                    >
                        <component :is="feature.icon" class="size-5" />
                    </span>
                    <ArrowRight
                        class="size-4 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:text-foreground"
                    />
                </div>
                <div>
                    <p class="text-sm font-medium text-muted-foreground">
                        {{ feature.title }}
                    </p>
                    <p class="text-2xl font-semibold tabular-nums">
                        {{ feature.value }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ feature.detail }}
                    </p>
                </div>
            </Link>
        </section>

        <div class="grid gap-6 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Sessions played</CardTitle>
                    <CardDescription>
                        Finished game sessions per week, last 8 weeks
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <dl class="grid grid-cols-3 gap-4">
                        <div class="rounded-lg bg-brand-soft p-3">
                            <dt class="text-xs text-muted-foreground">
                                This week
                            </dt>
                            <dd class="text-2xl font-semibold tabular-nums">
                                {{ sessions.this_week }}
                            </dd>
                        </div>
                        <div class="rounded-lg bg-brand-soft p-3">
                            <dt class="text-xs text-muted-foreground">
                                All time
                            </dt>
                            <dd class="text-2xl font-semibold tabular-nums">
                                {{ sessions.total }}
                            </dd>
                        </div>
                        <div class="rounded-lg bg-brand-soft p-3">
                            <dt class="text-xs text-muted-foreground">
                                Correct, last 30 days
                            </dt>
                            <dd class="text-2xl font-semibold tabular-nums">
                                {{
                                    sessions.accuracy_last_30_days === null
                                        ? '–'
                                        : `${sessions.accuracy_last_30_days}%`
                                }}
                            </dd>
                            <dd
                                v-if="sessions.answers_last_30_days > 0"
                                class="text-xs text-muted-foreground"
                            >
                                of {{ sessions.answers_last_30_days }} answers
                            </dd>
                        </div>
                    </dl>

                    <WeeklySessionsChart
                        v-if="sessions.total > 0"
                        :weeks="weeklySessions"
                    />
                    <div
                        v-else
                        class="flex flex-col items-center gap-2 rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
                    >
                        <Gamepad2 class="size-6" />
                        No sessions yet. Start one from a client&rsquo;s page
                        once you&rsquo;ve assigned an activity.
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Clinical hours</CardTitle>
                    <CardDescription
                        >Progress toward your targets</CardDescription
                    >
                </CardHeader>
                <CardContent class="space-y-5">
                    <div
                        v-for="category in hours.categories"
                        :key="category.hours_type"
                        class="space-y-2"
                    >
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="font-medium">{{ category.label }}</p>
                            <p
                                class="text-sm text-muted-foreground tabular-nums"
                            >
                                <span class="font-semibold text-foreground">
                                    {{ toHours(category.completed_minutes) }}
                                </span>
                                / {{ toHours(category.target_minutes) }} h
                            </p>
                        </div>
                        <div
                            class="h-2.5 overflow-hidden rounded-full bg-muted"
                            role="progressbar"
                            :aria-label="`${category.label} hours`"
                            :aria-valuenow="Math.min(100, category.percentage)"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        >
                            <div
                                class="h-full rounded-full bg-linear-to-r from-brand-violet via-brand-indigo to-brand-blue"
                                :style="{
                                    width: `${Math.min(100, category.percentage)}%`,
                                }"
                            />
                        </div>
                        <p
                            class="flex items-center gap-1 text-xs text-muted-foreground"
                        >
                            <template v-if="category.is_completed">
                                <CircleCheck
                                    class="size-3.5 text-emerald-600"
                                />
                                Target reached
                            </template>
                            <template v-else>
                                {{ toHours(category.remaining_minutes) }} h to
                                go · {{ category.percentage }}%
                            </template>
                        </p>
                    </div>
                    <Button variant="outline" class="w-full" as-child>
                        <Link :href="ClinicalLogFormController.index()">
                            Open clinical hours
                            <ArrowRight />
                        </Link>
                    </Button>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Recent sessions</CardTitle>
                    <CardDescription>
                        The latest finished sessions with your clients
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <ul v-if="recentSessions.length" class="divide-y">
                        <li v-for="session in recentSessions" :key="session.id">
                            <Link
                                :href="
                                    PlaySessionController.summary(session.id)
                                "
                                class="-mx-2 flex items-center gap-3 rounded-md px-2 py-3 transition-colors hover:bg-muted/60"
                            >
                                <span
                                    class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-soft text-xs font-semibold text-brand-ink"
                                >
                                    {{ session.client_reference.slice(0, 4) }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium">
                                        {{ session.client_reference }} ·
                                        {{ session.activity_title }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ session.game_type_label }} ·
                                        {{
                                            formatSessionTime(session.ended_at)
                                        }}
                                        · {{ session.answers }}
                                        {{
                                            session.answers === 1
                                                ? 'answer'
                                                : 'answers'
                                        }}
                                    </p>
                                </div>
                                <div class="w-24 shrink-0 space-y-1 text-right">
                                    <p
                                        class="text-sm font-semibold tabular-nums"
                                    >
                                        {{ session.accuracy }}%
                                    </p>
                                    <div
                                        class="h-1.5 overflow-hidden rounded-full bg-muted"
                                    >
                                        <div
                                            class="ml-auto h-full rounded-full bg-brand-chart"
                                            :style="{
                                                width: `${session.accuracy}%`,
                                            }"
                                        />
                                    </div>
                                </div>
                            </Link>
                        </li>
                    </ul>
                    <p
                        v-else
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        Finished sessions will show here.
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Goals in progress</CardTitle>
                    <CardDescription>
                        {{ goals.active_count }} active ·
                        {{ goals.achieved_count }} achieved
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <Link
                        v-for="goal in goals.active"
                        :key="goal.id"
                        :href="ClientController.show(goal.client_id)"
                        class="flex gap-3 rounded-lg border p-3 transition-colors hover:border-brand-indigo/40 hover:bg-muted/40"
                    >
                        <Target class="mt-0.5 size-4 shrink-0 text-brand-ink" />
                        <div class="min-w-0">
                            <p class="text-sm font-medium">
                                {{ goal.description }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ goal.client_reference }} · aim for
                                {{ goal.target_accuracy }}% correct
                            </p>
                        </div>
                    </Link>
                    <p
                        v-if="goals.active.length === 0"
                        class="py-4 text-center text-sm text-muted-foreground"
                    >
                        Add goals from a client&rsquo;s page to track their
                        progress here.
                    </p>
                    <p
                        v-if="goals.achieved_count > 0"
                        class="flex items-center gap-2 rounded-lg bg-brand-soft px-3 py-2 text-sm"
                    >
                        <Trophy class="size-4 text-brand-ink" />
                        {{ goals.achieved_count }}
                        {{ goals.achieved_count === 1 ? 'goal' : 'goals' }}
                        achieved so far
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
