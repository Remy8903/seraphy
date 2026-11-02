<script setup lang="ts">
import { Form, Head, Link, setLayoutProps, useForm } from '@inertiajs/vue3';
import {
    Archive,
    ArchiveRestore,
    ClipboardCheck,
    ClipboardPlus,
    FileDown,
    Pencil,
    Play,
    Plus,
    Trash2,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { create as createActivity } from '@/actions/App/Http/Controllers/ActivityController';
import {
    destroy as unassign,
    store as assign,
} from '@/actions/App/Http/Controllers/AssignmentController';
import ClientArchiveController from '@/actions/App/Http/Controllers/ClientArchiveController';
import ClientProgressReportController from '@/actions/App/Http/Controllers/ClientProgressReportController';
import {
    destroy,
    edit,
    index,
    show,
} from '@/actions/App/Http/Controllers/ClientController';
import { create as addToClinicalHours } from '@/actions/App/Http/Controllers/ClinicalHoursLinkController';
import { edit as editLogForm } from '@/actions/App/Http/Controllers/ClinicalLogFormController';
import {
    show as showSession,
    store as startSession,
    summary as sessionSummary,
} from '@/actions/App/Http/Controllers/PlaySessionController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import AccuracyChart from '@/components/therapy/AccuracyChart.vue';
import GoalsCard from '@/components/therapy/GoalsCard.vue';
import SaveOfflineButton from '@/components/therapy/SaveOfflineButton.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { categoryLabel } from '@/lib/therapy';
import type {
    AssignedActivity,
    ClientDetails,
    ClientProgress,
    ClientSessionListItem,
    GoalOptions,
    TherapyGoal,
    UnassignedActivity,
} from '@/types';

const props = defineProps<{
    client: ClientDetails;
    activities: AssignedActivity[];
    unassignedActivities: UnassignedActivity[];
    sessions: ClientSessionListItem[];
    progress: ClientProgress;
    goals: TherapyGoal[];
    goalOptions: GoalOptions;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Clients', href: index() },
        { title: props.client.reference_code, href: show(props.client.id) },
    ],
});

const isDeleteDialogOpen = ref(false);

/**
 * Get the current semester as ISO dates: September–February or March–August.
 */
function currentSemester(): { from: string; to: string } {
    const today = new Date();
    const month = today.getMonth() + 1;
    const year = today.getFullYear();
    const isoDate = (date: Date): string =>
        `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

    if (month >= 3 && month <= 8) {
        return { from: `${year}-03-01`, to: `${year}-08-31` };
    }

    const startYear = month >= 9 ? year : year - 1;

    return {
        from: `${startYear}-09-01`,
        to: isoDate(new Date(startYear + 1, 2, 0)),
    };
}

const isReportDialogOpen = ref(false);
const reportRange = ref(currentSemester());
const reportUrl = computed(() =>
    ClientProgressReportController.url(props.client.id, {
        query: reportRange.value,
    }),
);
const isReportRangeValid = computed(
    () =>
        !!reportRange.value.from &&
        !!reportRange.value.to &&
        reportRange.value.from <= reportRange.value.to,
);

const assignForm = useForm({ activity_id: '' });

function assignActivity(): void {
    assignForm.submit(assign(props.client.id), {
        preserveScroll: true,
        onSuccess: () => assignForm.reset(),
    });
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function latestAccuracy(activityId: number): number | null {
    const sessions = props.progress.activities.find(
        (activity) => activity.activity_id === activityId,
    )?.sessions;

    return sessions?.length ? sessions[sessions.length - 1].accuracy : null;
}

/** Accuracy tables for sort it word groups and follow directions step counts. */
const breakdowns = computed(() =>
    [
        {
            title: 'By word group (Sort it)',
            heading: 'Group',
            rows: props.progress.categories.map((group) => ({
                ...group,
                label: categoryLabel(group.category),
            })),
        },
        {
            title: 'By number of steps (Follow directions)',
            heading: 'Steps',
            rows: props.progress.steps.map((step) => ({
                ...step,
                label: `${step.steps}-step`,
            })),
        },
    ].filter((breakdown) => breakdown.rows.length > 0),
);
</script>

<template>
    <Head :title="client.reference_code" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-2">
                <Heading
                    :title="client.reference_code"
                    :description="client.diagnosis ?? 'No diagnosis recorded'"
                    variant="small"
                />
                <div class="flex flex-wrap gap-2">
                    <Badge variant="secondary">{{ client.age_group }}</Badge>
                    <Badge variant="outline">{{ client.language }}</Badge>
                    <Badge v-if="client.is_archived" variant="destructive">
                        Archived
                    </Badge>
                </div>
                <p
                    v-if="client.notes"
                    class="max-w-prose text-sm whitespace-pre-line text-muted-foreground"
                >
                    {{ client.notes }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link :href="edit(client.id)">
                        <Pencil />
                        Edit
                    </Link>
                </Button>
                <Form
                    v-bind="ClientArchiveController.form(client.id)"
                    :options="{ preserveScroll: true }"
                    v-slot="{ processing }"
                >
                    <Button
                        type="submit"
                        variant="outline"
                        :disabled="processing"
                    >
                        <ArchiveRestore v-if="client.is_archived" />
                        <Archive v-else />
                        {{ client.is_archived ? 'Restore' : 'Archive' }}
                    </Button>
                </Form>
                <Button variant="outline" @click="isReportDialogOpen = true">
                    <FileDown />
                    Progress report
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label="Delete client"
                    @click="isDeleteDialogOpen = true"
                >
                    <Trash2 />
                </Button>
            </div>
        </div>

        <GoalsCard
            :client-id="client.id"
            :goals="goals"
            :options="goalOptions"
        />

        <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
            <Card>
                <CardHeader>
                    <CardTitle>Therapy plan</CardTitle>
                    <CardDescription>
                        Activities assigned to this client. Start one to play it
                        together.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <p
                        v-if="client.is_archived"
                        class="rounded-md bg-muted p-3 text-sm text-muted-foreground"
                    >
                        Restore this client to start new sessions.
                    </p>

                    <ul
                        v-if="activities.length"
                        class="divide-y rounded-md border"
                    >
                        <li
                            v-for="activity in activities"
                            :key="activity.id"
                            class="flex items-center gap-3 p-3"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">
                                    {{ activity.title }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ activity.game_type_label }} ·
                                    {{ activity.language }} ·
                                    {{ activity.items_count }}
                                    {{
                                        activity.items_count === 1
                                            ? 'word'
                                            : 'words'
                                    }}
                                    <template
                                        v-if="
                                            latestAccuracy(activity.id) !== null
                                        "
                                    >
                                        · last
                                        {{ latestAccuracy(activity.id) }}%
                                    </template>
                                </p>
                            </div>
                            <Form
                                v-if="!client.is_archived"
                                v-bind="startSession.form()"
                                v-slot="{ processing }"
                            >
                                <input
                                    type="hidden"
                                    name="client_id"
                                    :value="client.id"
                                />
                                <input
                                    type="hidden"
                                    name="activity_id"
                                    :value="activity.id"
                                />
                                <Button
                                    type="submit"
                                    size="sm"
                                    :disabled="processing"
                                >
                                    <Play />
                                    Start
                                </Button>
                            </Form>
                            <SaveOfflineButton
                                v-if="!client.is_archived"
                                :client-id="client.id"
                                :activity-id="activity.id"
                                :activity-title="activity.title"
                            />
                            <Form
                                v-bind="unassign.form([client.id, activity.id])"
                                :options="{ preserveScroll: true }"
                                v-slot="{ processing }"
                            >
                                <Button
                                    type="submit"
                                    variant="ghost"
                                    size="icon"
                                    :disabled="processing"
                                    :aria-label="`Remove ${activity.title} from plan`"
                                >
                                    <X />
                                </Button>
                            </Form>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">
                        No activities assigned yet.
                    </p>

                    <form
                        v-if="unassignedActivities.length"
                        class="space-y-2"
                        @submit.prevent="assignActivity"
                    >
                        <div class="flex gap-2">
                            <Select v-model="assignForm.activity_id">
                                <SelectTrigger
                                    class="w-full"
                                    aria-label="Activity to assign"
                                >
                                    <SelectValue
                                        placeholder="Choose an activity"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="activity in unassignedActivities"
                                        :key="activity.id"
                                        :value="String(activity.id)"
                                    >
                                        {{ activity.title }} ·
                                        {{ activity.game_type_label }} ·
                                        {{ activity.language }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Button
                                type="submit"
                                variant="outline"
                                :disabled="
                                    !assignForm.activity_id ||
                                    assignForm.processing
                                "
                            >
                                <Plus />
                                Assign
                            </Button>
                        </div>
                        <InputError :message="assignForm.errors.activity_id" />
                    </form>

                    <Button variant="link" class="h-auto px-0" as-child>
                        <Link :href="createActivity()">
                            Create a new activity
                        </Link>
                    </Button>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Progress</CardTitle>
                    <CardDescription>
                        Accuracy is the share of answers correct without a
                        prompt, from finished sessions.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <p
                        v-if="progress.activities.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Progress appears here after the first finished session.
                    </p>

                    <div
                        v-for="activity in progress.activities"
                        :key="activity.activity_id"
                        class="space-y-1"
                    >
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="font-medium">{{ activity.title }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ activity.sessions.length }}
                                {{
                                    activity.sessions.length === 1
                                        ? 'session'
                                        : 'sessions'
                                }}
                            </p>
                        </div>
                        <AccuracyChart
                            :sessions="activity.sessions"
                            :label="activity.title"
                        />
                    </div>

                    <div v-if="progress.sounds.length" class="overflow-x-auto">
                        <p class="mb-2 font-medium">By target sound</p>
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="border-b text-left text-xs text-muted-foreground"
                                >
                                    <th class="py-2 pr-2 font-medium">Sound</th>
                                    <th class="px-2 py-2 font-medium">
                                        Position
                                    </th>
                                    <th
                                        class="px-2 py-2 text-right font-medium"
                                    >
                                        Answers
                                    </th>
                                    <th
                                        class="px-2 py-2 text-right font-medium"
                                    >
                                        Prompted
                                    </th>
                                    <th
                                        class="py-2 pl-2 text-right font-medium"
                                    >
                                        Accuracy
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="sound in progress.sounds"
                                    :key="`${sound.target_sound}-${sound.word_position}`"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-2 pr-2 font-medium">
                                        /{{ sound.target_sound }}/
                                    </td>
                                    <td class="px-2 py-2">
                                        {{ sound.word_position_label ?? '—' }}
                                    </td>
                                    <td
                                        class="px-2 py-2 text-right tabular-nums"
                                    >
                                        {{ sound.total }}
                                    </td>
                                    <td
                                        class="px-2 py-2 text-right tabular-nums"
                                    >
                                        {{ sound.prompted }}
                                    </td>
                                    <td
                                        class="py-2 pl-2 text-right font-medium tabular-nums"
                                    >
                                        {{ sound.accuracy }}%
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-for="breakdown in breakdowns"
                        :key="breakdown.title"
                        class="overflow-x-auto"
                    >
                        <p class="mb-2 font-medium">{{ breakdown.title }}</p>
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="border-b text-left text-xs text-muted-foreground"
                                >
                                    <th class="py-2 pr-2 font-medium">
                                        {{ breakdown.heading }}
                                    </th>
                                    <th
                                        class="px-2 py-2 text-right font-medium"
                                    >
                                        Answers
                                    </th>
                                    <th
                                        class="px-2 py-2 text-right font-medium"
                                    >
                                        Prompted
                                    </th>
                                    <th
                                        class="py-2 pl-2 text-right font-medium"
                                    >
                                        Accuracy
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in breakdown.rows"
                                    :key="row.label"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-2 pr-2 font-medium">
                                        {{ row.label }}
                                    </td>
                                    <td
                                        class="px-2 py-2 text-right tabular-nums"
                                    >
                                        {{ row.total }}
                                    </td>
                                    <td
                                        class="px-2 py-2 text-right tabular-nums"
                                    >
                                        {{ row.prompted }}
                                    </td>
                                    <td
                                        class="py-2 pl-2 text-right font-medium tabular-nums"
                                    >
                                        {{ row.accuracy }}%
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Recent sessions</CardTitle>
            </CardHeader>
            <CardContent>
                <p
                    v-if="sessions.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No sessions yet.
                </p>
                <ul v-else class="divide-y">
                    <li
                        v-for="session in sessions"
                        :key="session.id"
                        class="flex items-center justify-between gap-3 py-2 text-sm"
                    >
                        <div>
                            <p class="font-medium">
                                {{ session.activity_title }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ formatDate(session.started_at) }} ·
                                {{ session.total }}
                                {{ session.total === 1 ? 'answer' : 'answers' }}
                            </p>
                        </div>
                        <Button
                            v-if="!session.is_finished"
                            size="sm"
                            variant="outline"
                            as-child
                        >
                            <Link :href="showSession(session.id)">Resume</Link>
                        </Button>
                        <div v-else class="flex items-center gap-1">
                            <Button
                                v-if="session.clinical_log_form_id"
                                variant="ghost"
                                size="icon"
                                as-child
                            >
                                <Link
                                    :href="
                                        editLogForm(
                                            session.clinical_log_form_id,
                                        )
                                    "
                                    title="Added to clinical hours"
                                    aria-label="Open the log form this session was added to"
                                >
                                    <ClipboardCheck class="text-emerald-600" />
                                </Link>
                            </Button>
                            <Button v-else variant="ghost" size="icon" as-child>
                                <Link
                                    :href="
                                        addToClinicalHours(client.id, {
                                            query: { date: session.date },
                                        })
                                    "
                                    title="Add to clinical hours"
                                    :aria-label="`Add ${session.activity_title} session to clinical hours`"
                                >
                                    <ClipboardPlus />
                                </Link>
                            </Button>
                            <Link
                                :href="sessionSummary(session.id)"
                                class="font-medium tabular-nums underline-offset-4 hover:underline"
                            >
                                {{ session.accuracy }}%
                            </Link>
                        </div>
                    </li>
                </ul>
            </CardContent>
        </Card>

        <Dialog v-model:open="isReportDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Download progress report</DialogTitle>
                    <DialogDescription>
                        A PDF of goals, accuracy and sessions in the period. The
                        client is shown by reference code only.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="report_from">From</Label>
                        <Input
                            id="report_from"
                            v-model="reportRange.from"
                            type="date"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="report_to">To</Label>
                        <Input
                            id="report_to"
                            v-model="reportRange.to"
                            type="date"
                            :min="reportRange.from"
                        />
                    </div>
                </div>
                <InputError
                    v-if="!isReportRangeValid"
                    message="Choose a start date on or before the end date."
                />
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button
                        v-if="isReportRangeValid"
                        as-child
                        @click="isReportDialogOpen = false"
                    >
                        <a :href="reportUrl" download>
                            <FileDown />
                            Download PDF
                        </a>
                    </Button>
                    <Button v-else disabled>
                        <FileDown />
                        Download PDF
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isDeleteDialogOpen">
            <DialogContent>
                <Form
                    v-bind="destroy.form(client.id)"
                    class="space-y-6"
                    v-slot="{ processing }"
                >
                    <DialogHeader class="space-y-3">
                        <DialogTitle
                            >Delete {{ client.reference_code }}?</DialogTitle
                        >
                        <DialogDescription>
                            All of this client's sessions and recorded answers
                            will be deleted. This cannot be undone. Archive the
                            client instead if you may need their progress later.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary">Cancel</Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="processing"
                        >
                            Delete client
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
