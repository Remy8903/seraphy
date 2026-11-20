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
    Sparkles,
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
import PipMascot from '@/components/PipMascot.vue';
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
    /** Soft, contrasting colors for each workspace shortcut. */
    tint: string;
};

const features = computed<FeatureTile[]>(() => [
    {
        title: 'Clinical hours',
        href: ClinicalLogFormController.index(),
        icon: Clock,
        value: `${toHours(loggedMinutes.value)} h`,
        detail: `logged in ${props.hours.forms_count} ${props.hours.forms_count === 1 ? 'form' : 'forms'}`,
        tint: 'bg-[#eee4fa] text-[#7952ae] dark:bg-[#403050] dark:text-[#d4b5f4]',
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
        tint: 'bg-[#e1f1ed] text-[#478378] dark:bg-[#294640] dark:text-[#a9ded1]',
    },
    {
        title: 'Activities',
        href: ActivityController.index(),
        icon: Gamepad2,
        value: String(props.counts.activities),
        detail: props.counts.activities === 1 ? 'game set up' : 'games set up',
        tint: 'bg-[#fbeadd] text-[#a06b43] dark:bg-[#493427] dark:text-[#f0c2a0]',
    },
    {
        title: 'Word library',
        href: ContentItemController.index(),
        icon: BookOpen,
        value: String(props.counts.words),
        detail: `pictures, ${props.counts.own_words} of your own`,
        tint: 'bg-[#e6effb] text-[#597caf] dark:bg-[#29394d] dark:text-[#b4cbed]',
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
        tint: 'bg-[#f7e7ef] text-[#ab6c90] dark:bg-[#493040] dark:text-[#edb8d6]',
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

    <div
        class="flex flex-1 flex-col gap-6 bg-[#fbf9f5] p-4 sm:p-6 dark:bg-[#17131f]"
    >
        <section
            class="relative isolate overflow-hidden rounded-[1.75rem] border border-[#e9dff1] bg-[#f1eaf8] p-6 text-[#302740] sm:p-8 dark:border-[#4a365a] dark:bg-[#2d2139] dark:text-[#f5effc]"
        >
            <div
                class="absolute -top-24 right-0 -z-10 size-72 rounded-full bg-[#e4d3f4] blur-3xl dark:bg-[#67447c]/30"
                aria-hidden="true"
            />
            <div
                class="absolute -bottom-32 left-1/3 -z-10 size-64 rounded-full bg-[#f8dfd5]/70 blur-3xl dark:bg-[#84625f]/15"
                aria-hidden="true"
            />
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div
                    class="relative z-10 flex max-w-2xl flex-col items-start gap-4"
                >
                    <p
                        class="inline-flex items-center gap-2 rounded-full bg-white/70 px-3 py-1.5 text-[0.6875rem] font-semibold tracking-[0.12em] text-[#80629a] dark:bg-white/10 dark:text-[#d5b8ea]"
                    >
                        <Sparkles class="size-3.5" />
                        YOUR LITTLE CORNER OF CARE
                    </p>
                    <div>
                        <p class="text-sm text-[#806f8c] dark:text-[#c5b2d1]">
                            {{ today }}
                        </p>
                        <h1
                            class="mt-1 text-3xl font-semibold tracking-[-0.04em] sm:text-4xl"
                        >
                            {{ greeting }}, {{ firstName
                            }}<span class="text-[#9569c0] dark:text-[#cda8eb]"
                                >.</span
                            >
                        </h1>
                        <p
                            class="mt-2 max-w-lg text-sm leading-6 text-[#786b82] dark:text-[#c8b7d1]"
                        >
                            <template v-if="sessions.this_week > 0">
                                {{ sessions.this_week }}
                                {{
                                    sessions.this_week === 1
                                        ? 'session'
                                        : 'sessions'
                                }}
                                played this week. Every little step counts.
                            </template>
                            <template v-else>
                                A fresh day for little wins and brighter voices.
                                Pip is cheering you on!
                            </template>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-1">
                        <Button
                            as-child
                            class="rounded-full bg-[#7850b7] text-white shadow-sm hover:bg-[#654098]"
                        >
                            <Link :href="ClinicalLogFormController.create()">
                                <Plus />
                                Log hours
                            </Link>
                        </Button>
                        <Button
                            as-child
                            variant="outline"
                            class="rounded-full border-[#e2d4eb] bg-white/70 text-[#634a76] hover:bg-white hover:text-[#634a76] dark:border-[#5c456d] dark:bg-white/10 dark:text-[#eadcf3] dark:hover:bg-white/20 dark:hover:text-white"
                        >
                            <Link :href="ClientController.create()">
                                <Users />
                                Add client
                            </Link>
                        </Button>
                        <Button
                            as-child
                            variant="outline"
                            class="rounded-full border-[#e2d4eb] bg-white/70 text-[#634a76] hover:bg-white hover:text-[#634a76] dark:border-[#5c456d] dark:bg-white/10 dark:text-[#eadcf3] dark:hover:bg-white/20 dark:hover:text-white"
                        >
                            <Link :href="ActivityController.create()">
                                <Gamepad2 />
                                New activity
                            </Link>
                        </Button>
                    </div>
                </div>
                <div
                    class="relative mx-auto flex w-40 shrink-0 items-center justify-center sm:mx-0 sm:w-44 lg:mr-6 lg:w-52"
                >
                    <span
                        aria-hidden="true"
                        class="absolute size-36 rounded-full border border-dashed border-[#d1b8e3] bg-white/35 sm:size-40 lg:size-48 dark:border-[#84639b] dark:bg-white/5"
                    />
                    <PipMascot
                        class="relative w-full"
                        title="Pip welcomes you to your dashboard"
                    />
                </div>
            </div>
        </section>

        <div class="flex items-end justify-between gap-4">
            <div>
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.14em] text-[#9677a9] dark:text-[#c2a2d5]"
                >
                    YOUR WORKSPACE AT A GLANCE
                </p>
                <h2
                    class="mt-1 text-xl font-semibold tracking-tight text-[#302740] dark:text-[#f5effc]"
                >
                    The little things, all together.
                </h2>
            </div>
        </div>
        <section
            aria-label="Features"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5"
        >
            <Link
                v-for="feature in features"
                :key="feature.title"
                :href="feature.href"
                class="group flex flex-col gap-4 rounded-[1.35rem] border border-[#eee7ef] bg-[#fffdfb] p-5 text-[#302740] transition-all hover:-translate-y-0.5 hover:border-[#cbb2dd] hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#9466c1] dark:border-[#43344e] dark:bg-[#241d2c] dark:text-[#f5effc]"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="flex size-11 items-center justify-center rounded-2xl"
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
            <Card
                class="rounded-[1.35rem] border-[#eee7ef] bg-[#fffdfb] shadow-none lg:col-span-2 dark:border-[#43344e] dark:bg-[#241d2c]"
            >
                <CardHeader>
                    <CardTitle>Sessions played</CardTitle>
                    <CardDescription>
                        Finished game sessions per week, last 8 weeks
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <dl class="grid grid-cols-3 gap-4">
                        <div
                            class="rounded-xl bg-[#f4eef9] p-3 dark:bg-[#382a43]"
                        >
                            <dt class="text-xs text-muted-foreground">
                                This week
                            </dt>
                            <dd class="text-2xl font-semibold tabular-nums">
                                {{ sessions.this_week }}
                            </dd>
                        </div>
                        <div
                            class="rounded-xl bg-[#edf6f3] p-3 dark:bg-[#263c39]"
                        >
                            <dt class="text-xs text-muted-foreground">
                                All time
                            </dt>
                            <dd class="text-2xl font-semibold tabular-nums">
                                {{ sessions.total }}
                            </dd>
                        </div>
                        <div
                            class="rounded-xl bg-[#fcf0e7] p-3 dark:bg-[#423127]"
                        >
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
                        class="flex flex-col items-center gap-2 rounded-2xl border border-dashed border-[#dfd0ea] bg-[#fbf8fd] p-8 text-center text-sm text-muted-foreground dark:border-[#5a4569] dark:bg-[#2d2236]"
                    >
                        <PipMascot
                            class="h-20 w-24"
                            title="Pip is ready for your first session"
                        />
                        No sessions yet. Start one from a client&rsquo;s page
                        once you&rsquo;ve assigned an activity.
                    </div>
                </CardContent>
            </Card>

            <Card
                class="rounded-[1.35rem] border-[#eee7ef] bg-[#fffdfb] shadow-none dark:border-[#43344e] dark:bg-[#241d2c]"
            >
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
                                class="h-full rounded-full bg-linear-to-r from-[#a782db] to-[#7a54b5]"
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
            <Card
                class="rounded-[1.35rem] border-[#eee7ef] bg-[#fffdfb] shadow-none lg:col-span-2 dark:border-[#43344e] dark:bg-[#241d2c]"
            >
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
                                    class="flex size-9 shrink-0 items-center justify-center rounded-full bg-[#f1e9f8] text-xs font-semibold text-[#78519c] dark:bg-[#433150] dark:text-[#dbc2ed]"
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

            <Card
                class="rounded-[1.35rem] border-[#eee7ef] bg-[#fffdfb] shadow-none dark:border-[#43344e] dark:bg-[#241d2c]"
            >
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
                        class="flex items-center gap-2 rounded-lg bg-[#f3edf8] px-3 py-2 text-sm dark:bg-[#3a2a46]"
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
