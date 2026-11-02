<script setup lang="ts">
import { Head, Link, setLayoutProps, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ClipboardCheck, ClipboardPlus, Target } from '@lucide/vue';
import { computed, onMounted } from 'vue';
import {
    index as clients,
    show as showClient,
} from '@/actions/App/Http/Controllers/ClientController';
import { create as addToClinicalHours } from '@/actions/App/Http/Controllers/ClinicalHoursLinkController';
import { edit as editLogForm } from '@/actions/App/Http/Controllers/ClinicalLogFormController';
import { update } from '@/actions/App/Http/Controllers/PlaySessionController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PictureCard from '@/components/therapy/PictureCard.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useGameSounds } from '@/composables/useGameSounds';
import { categoryIcon, categoryLabel } from '@/lib/therapy';
import type { SessionGoal, SessionSummary } from '@/types';

const props = defineProps<{
    session: {
        id: number;
        started_at: string;
        date: string;
        clinical_log_form_id: number | null;
        duration_minutes: number;
        notes: string;
        stickers: string[];
    };
    client: {
        id: number;
        reference_code: string;
    };
    activity: {
        title: string;
        game_type_label: string;
    };
    summary: SessionSummary;
    goals: SessionGoal[];
}>();

const page = usePage();

/** Goals this session has just achieved, flashed once when the session is finished. */
const achievedGoals = computed(
    () => (page.flash.achievedGoals as string[] | undefined) ?? [],
);

onMounted(() => {
    if (achievedGoals.value.length > 0) {
        useGameSounds().playFinish();
    }
});

setLayoutProps({
    breadcrumbs: [
        { title: 'Clients', href: clients() },
        {
            title: props.client.reference_code,
            href: showClient(props.client.id),
        },
        { title: 'Session results', href: '#' },
    ],
});

const notesForm = useForm({ notes: props.session.notes });

function saveNotes(): void {
    notesForm.submit(update(props.session.id), { preserveScroll: true });
}

const tallies = [
    { key: 'correct', label: 'Correct', classes: 'text-emerald-600' },
    { key: 'prompted', label: 'With prompt', classes: 'text-amber-600' },
    { key: 'incorrect', label: 'Incorrect', classes: 'text-rose-600' },
] as const;

const hasConfusions = computed(() =>
    props.summary.words.some((word) => word.confusions.length > 0),
);

const resultLabels = {
    correct: { label: 'Correct', classes: 'text-emerald-600' },
    prompted: { label: 'With prompt', classes: 'text-amber-600' },
    incorrect: { label: 'Incorrect', classes: 'text-rose-600' },
} as const;

function seconds(milliseconds: number): string {
    return `${(milliseconds / 1000).toFixed(1)} s`;
}

const storyResultLabels = {
    correct: { label: 'Right first time', classes: 'text-emerald-600' },
    prompted: { label: 'With help or retry', classes: 'text-amber-600' },
    incorrect: { label: 'Skipped', classes: 'text-rose-600' },
} as const;

const startedAt = new Date(props.session.started_at).toLocaleString(undefined, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
});
</script>

<template>
    <Head title="Session results" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="activity.title"
                :description="`${client.reference_code} · ${activity.game_type_label} · ${startedAt} · ${session.duration_minutes} min`"
            />
            <div class="flex flex-wrap gap-2">
                <Button
                    v-if="session.clinical_log_form_id"
                    variant="ghost"
                    as-child
                >
                    <Link :href="editLogForm(session.clinical_log_form_id)">
                        <ClipboardCheck />
                        Added to clinical hours
                    </Link>
                </Button>
                <Button v-else variant="outline" as-child>
                    <Link
                        :href="
                            addToClinicalHours(client.id, {
                                query: { date: session.date },
                            })
                        "
                    >
                        <ClipboardPlus />
                        Add to clinical hours
                    </Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="showClient(client.id)">
                        <ArrowLeft />
                        Back to client
                    </Link>
                </Button>
            </div>
        </div>

        <div
            v-if="achievedGoals.length"
            class="flex items-center gap-4 rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100"
            role="status"
        >
            <span class="animate-bounce text-5xl leading-none">🏆</span>
            <div>
                <p class="text-lg font-semibold">
                    {{
                        achievedGoals.length === 1
                            ? 'Goal achieved!'
                            : `${achievedGoals.length} goals achieved!`
                    }}
                </p>
                <ul class="text-sm">
                    <li v-for="goal in achievedGoals" :key="goal">
                        {{ goal }}
                    </li>
                </ul>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-4">
            <Card>
                <CardHeader>
                    <CardDescription>Accuracy</CardDescription>
                    <CardTitle class="text-3xl tabular-nums">
                        {{ summary.accuracy }}%
                    </CardTitle>
                </CardHeader>
            </Card>
            <Card v-for="tally in tallies" :key="tally.key">
                <CardHeader>
                    <CardDescription>{{ tally.label }}</CardDescription>
                    <CardTitle
                        :class="['text-3xl tabular-nums', tally.classes]"
                    >
                        {{ summary[tally.key] }}
                    </CardTitle>
                </CardHeader>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
            <div class="flex flex-col gap-6">
                <Card v-if="summary.categories.length">
                    <CardHeader>
                        <CardTitle>Results by group</CardTitle>
                        <CardDescription>
                            Which groups the child sorted correctly, and where
                            pictures went instead
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="border-b text-left text-xs text-muted-foreground"
                                >
                                    <th class="py-2 pr-2 font-medium">Group</th>
                                    <th
                                        v-for="tally in tallies"
                                        :key="tally.key"
                                        class="px-2 py-2 text-right font-medium"
                                    >
                                        {{ tally.label }}
                                    </th>
                                    <th class="py-2 pl-2 font-medium">
                                        Sorted into instead
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="group in summary.categories"
                                    :key="group.category"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-2 pr-2 font-medium">
                                        {{ categoryIcon(group.category) }}
                                        {{ categoryLabel(group.category) }}
                                    </td>
                                    <td
                                        v-for="tally in tallies"
                                        :key="tally.key"
                                        class="px-2 py-2 text-right tabular-nums"
                                        :class="{
                                            'text-muted-foreground':
                                                !group[tally.key],
                                        }"
                                    >
                                        {{ group[tally.key] }}
                                    </td>
                                    <td class="py-2 pl-2 text-xs">
                                        <template
                                            v-if="group.confusions.length === 0"
                                        >
                                            —
                                        </template>
                                        <span
                                            v-for="confusion in group.confusions"
                                            :key="confusion.category"
                                            class="mr-2"
                                        >
                                            {{
                                                categoryLabel(
                                                    confusion.category,
                                                )
                                            }}
                                            <template
                                                v-if="confusion.count > 1"
                                            >
                                                ×{{ confusion.count }}
                                            </template>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <Card v-if="summary.directions.length">
                    <CardHeader>
                        <CardTitle>Results by direction</CardTitle>
                        <CardDescription>
                            <span
                                v-for="step in summary.steps"
                                :key="step.steps"
                                class="mr-3"
                            >
                                {{ step.steps }}-step:
                                <strong>{{ step.accuracy }}%</strong>
                                ({{ step.correct }}/{{ step.total }})
                            </span>
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ul class="divide-y">
                            <li
                                v-for="(
                                    direction, directionIndex
                                ) in summary.directions"
                                :key="directionIndex"
                                class="space-y-1 py-2"
                            >
                                <div
                                    class="flex flex-wrap items-baseline justify-between gap-2"
                                >
                                    <span class="font-medium">
                                        “{{ direction.text }}”
                                    </span>
                                    <span
                                        :class="[
                                            'text-sm font-medium',
                                            resultLabels[direction.result]
                                                .classes,
                                        ]"
                                    >
                                        {{
                                            resultLabels[direction.result].label
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-if="
                                        direction.result !== 'correct' &&
                                        direction.touched.length
                                    "
                                    class="flex flex-wrap items-center gap-1 text-xs text-muted-foreground"
                                >
                                    First try touched:
                                    <PictureCard
                                        v-for="(
                                            picture, index
                                        ) in direction.touched"
                                        :key="`${picture.id}-${index}`"
                                        :picture="picture"
                                        size="sm"
                                    />
                                    · expected:
                                    <PictureCard
                                        v-for="picture in direction.expected"
                                        :key="`expected-${picture.id}`"
                                        :picture="picture"
                                        size="sm"
                                    />
                                </div>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <Card v-if="summary.tries.length">
                    <CardHeader>
                        <CardTitle>Results by try</CardTitle>
                        <CardDescription>
                            Time in the target range and the average voice
                            level, relative to the calibrated normal voice (1.0
                            = normal). These are not decibels.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="border-b text-left text-xs text-muted-foreground"
                                >
                                    <th class="py-2 pr-2 font-medium">Try</th>
                                    <th class="px-2 py-2 font-medium">
                                        Result
                                    </th>
                                    <th
                                        class="px-2 py-2 text-right font-medium"
                                    >
                                        In range
                                    </th>
                                    <th
                                        class="py-2 pl-2 text-right font-medium"
                                    >
                                        Average level
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(attempt, tryIndex) in summary.tries"
                                    :key="tryIndex"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-2 pr-2 tabular-nums">
                                        {{ tryIndex + 1 }}
                                    </td>
                                    <td
                                        :class="[
                                            'px-2 py-2 font-medium',
                                            resultLabels[attempt.result]
                                                .classes,
                                        ]"
                                    >
                                        {{ resultLabels[attempt.result].label }}
                                    </td>
                                    <td
                                        class="px-2 py-2 text-right tabular-nums"
                                    >
                                        {{ seconds(attempt.held_ms) }}
                                    </td>
                                    <td
                                        class="py-2 pl-2 text-right tabular-nums"
                                    >
                                        {{
                                            attempt.average_level === null
                                                ? '—'
                                                : `${attempt.average_level.toFixed(2)}×`
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <Card v-if="summary.stories.length">
                    <CardHeader>
                        <CardTitle>Results by story</CardTitle>
                        <CardDescription>
                            {{ summary.stories.length }}
                            {{
                                summary.stories.length === 1
                                    ? 'story'
                                    : 'stories'
                            }}
                            in this session
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ul class="divide-y">
                            <li
                                v-for="(story, storyIndex) in summary.stories"
                                :key="storyIndex"
                                class="flex flex-wrap items-center gap-3 py-2"
                            >
                                <div class="flex items-center gap-1">
                                    <PictureCard
                                        v-for="card in story.cards"
                                        :key="card.id"
                                        :picture="card"
                                        size="sm"
                                    />
                                </div>
                                <span
                                    :class="[
                                        'text-sm font-medium',
                                        storyResultLabels[story.result].classes,
                                    ]"
                                >
                                    {{ storyResultLabels[story.result].label }}
                                </span>
                                <span class="text-xs text-muted-foreground">
                                    {{ story.attempts }}
                                    {{ story.attempts === 1 ? 'try' : 'tries' }}
                                </span>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <Card v-if="summary.words.length">
                    <CardHeader>
                        <CardTitle>Results by word</CardTitle>
                        <CardDescription>
                            {{ summary.total }} answers in this session
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="border-b text-left text-xs text-muted-foreground"
                                >
                                    <th class="py-2 pr-2 font-medium">Word</th>
                                    <th
                                        v-for="tally in tallies"
                                        :key="tally.key"
                                        class="px-2 py-2 text-right font-medium"
                                    >
                                        {{ tally.label }}
                                    </th>
                                    <th
                                        v-if="hasConfusions"
                                        class="py-2 pl-2 font-medium"
                                    >
                                        Chose instead
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="word in summary.words"
                                    :key="word.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-2 pr-2">
                                        <div class="flex items-center gap-2">
                                            <PictureCard
                                                :picture="word"
                                                size="sm"
                                            />
                                            <span class="font-medium">
                                                {{ word.word }}
                                            </span>
                                        </div>
                                    </td>
                                    <td
                                        v-for="tally in tallies"
                                        :key="tally.key"
                                        class="px-2 py-2 text-right tabular-nums"
                                        :class="{
                                            'text-muted-foreground':
                                                !word[tally.key],
                                        }"
                                    >
                                        {{ word[tally.key] }}
                                    </td>
                                    <td v-if="hasConfusions" class="py-2 pl-2">
                                        <div class="flex flex-wrap gap-2">
                                            <span
                                                v-for="confusion in word.confusions"
                                                :key="confusion.id"
                                                class="flex items-center gap-1"
                                                :title="confusion.word"
                                            >
                                                <PictureCard
                                                    :picture="confusion"
                                                    size="sm"
                                                />
                                                <span class="text-xs">
                                                    {{ confusion.word }}
                                                    <template
                                                        v-if="
                                                            confusion.count > 1
                                                        "
                                                    >
                                                        ×{{ confusion.count }}
                                                    </template>
                                                </span>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>

            <div class="flex flex-col gap-6">
                <Card v-if="session.stickers.length">
                    <CardHeader>
                        <CardTitle>
                            {{ session.stickers.length }}
                            {{
                                session.stickers.length === 1
                                    ? 'sticker'
                                    : 'stickers'
                            }}
                            earned!
                        </CardTitle>
                        <CardDescription>
                            For motivation only; stickers do not change the
                            results
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p
                            class="flex flex-wrap gap-1 text-4xl"
                            aria-label="Sticker board"
                        >
                            <span
                                v-for="(sticker, index) in session.stickers"
                                :key="index"
                            >
                                {{ sticker }}
                            </span>
                        </p>
                    </CardContent>
                </Card>

                <Card v-if="goals.length">
                    <CardHeader>
                        <CardTitle>Goals</CardTitle>
                        <CardDescription>
                            Goals this session counted toward
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ul class="space-y-3">
                            <li
                                v-for="goal in goals"
                                :key="goal.id"
                                class="flex items-start gap-3 text-sm"
                            >
                                <Target
                                    class="mt-0.5 size-4 shrink-0 text-primary"
                                />
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium">
                                        {{ goal.description }}
                                    </p>
                                    <p class="text-muted-foreground">
                                        <span
                                            :class="
                                                goal.met
                                                    ? 'font-medium text-emerald-600'
                                                    : ''
                                            "
                                        >
                                            {{ goal.accuracy }}%
                                        </span>
                                        (target {{ goal.target_accuracy }}%) ·
                                        <template
                                            v-if="goal.status === 'achieved'"
                                        >
                                            achieved
                                        </template>
                                        <template v-else>
                                            {{ goal.streak }} of
                                            {{ goal.consecutive_sessions }} in a
                                            row
                                        </template>
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Session notes</CardTitle>
                        <CardDescription>
                            Observations for your records. No personal details.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form class="space-y-3" @submit.prevent="saveNotes">
                            <Label for="notes" class="sr-only">Notes</Label>
                            <textarea
                                id="notes"
                                v-model="notesForm.notes"
                                rows="6"
                                maxlength="2000"
                                placeholder="e.g. Needed visual cue for /s/ blends, good attention"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive md:text-sm dark:bg-input/30"
                                :aria-invalid="!!notesForm.errors.notes"
                            />
                            <InputError :message="notesForm.errors.notes" />
                            <div class="flex justify-end">
                                <Button
                                    type="submit"
                                    :disabled="
                                        notesForm.processing ||
                                        !notesForm.isDirty
                                    "
                                >
                                    <Spinner v-if="notesForm.processing" />
                                    Save notes
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
