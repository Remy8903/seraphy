<script setup lang="ts">
import { Head, Link, setLayoutProps, useForm } from '@inertiajs/vue3';
import { ClipboardList } from '@lucide/vue';
import { computed } from 'vue';
import { index, show } from '@/actions/App/Http/Controllers/ClientController';
import { store } from '@/actions/App/Http/Controllers/ClinicalHoursLinkController';
import {
    create as createLogForm,
    edit as editLogForm,
} from '@/actions/App/Http/Controllers/ClinicalLogFormController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import type { SelectOption } from '@/types';

type DaySession = {
    id: number;
    activity_title: string;
    started_at: string;
    ended_at: string | null;
};

const props = defineProps<{
    client: { id: number; reference_code: string };
    date: string;
    sessions: DaySession[];
    loggedSessions: (DaySession & { clinical_log_form_id: number })[];
    forms: { id: number; label: string }[];
    entry: {
        clinical_log_form_id: number | null;
        date: string;
        age_group: string;
        rn_no: string;
        session_mode: string;
        session_type: string;
        diagnosis: string;
        hours: number;
        minutes: number;
    };
    options: {
        ageGroups: SelectOption[];
        sessionModes: SelectOption[];
        sessionTypes: SelectOption[];
    };
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Clients', href: index() },
        { title: props.client.reference_code, href: show(props.client.id) },
        { title: 'Add to clinical hours', href: '#' },
    ],
});

const form = useForm({
    ...props.entry,
    clinical_log_form_id: props.entry.clinical_log_form_id
        ? String(props.entry.clinical_log_form_id)
        : '',
});

const loggedFormIds = computed(() => [
    ...new Set(
        props.loggedSessions.map((session) => session.clinical_log_form_id),
    ),
]);

const formattedDate = new Date(`${props.date}T00:00:00`).toLocaleDateString(
    undefined,
    { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' },
);

function formatTime(date: string | null): string {
    return date
        ? new Date(date).toLocaleTimeString(undefined, {
              hour: 'numeric',
              minute: '2-digit',
          })
        : '—';
}

function submit(): void {
    form.submit(store(props.client.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Add to clinical hours" />

    <div class="flex max-w-3xl flex-1 flex-col gap-6 p-4">
        <Heading
            title="Add to clinical hours"
            :description="`${client.reference_code} · ${formattedDate}`"
        />

        <Card>
            <CardHeader>
                <CardTitle>Sessions this day</CardTitle>
                <CardDescription>
                    All of these are added as one row on your log form.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
                <ul v-if="sessions.length" class="divide-y rounded-md border">
                    <li
                        v-for="session in sessions"
                        :key="session.id"
                        class="flex items-center justify-between gap-3 p-3 text-sm"
                    >
                        <span class="font-medium">
                            {{ session.activity_title }}
                        </span>
                        <span class="text-muted-foreground tabular-nums">
                            {{ formatTime(session.started_at) }} –
                            {{ formatTime(session.ended_at) }}
                        </span>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    There are no finished sessions on this day that still need
                    adding.
                </p>

                <p
                    v-if="loggedSessions.length"
                    class="text-sm text-muted-foreground"
                >
                    {{ loggedSessions.length }}
                    {{
                        loggedSessions.length === 1
                            ? 'session this day is'
                            : 'sessions this day are'
                    }}
                    already in your clinical hours:
                    <template
                        v-for="(formId, position) in loggedFormIds"
                        :key="formId"
                    >
                        <template v-if="position > 0">, </template>
                        <Link
                            :href="editLogForm(formId)"
                            class="font-medium text-foreground underline underline-offset-4"
                        >
                            open log form
                        </Link>
                    </template>
                </p>
            </CardContent>
        </Card>

        <Card v-if="sessions.length && forms.length === 0">
            <CardHeader>
                <CardTitle>No log form yet</CardTitle>
                <CardDescription>
                    Sessions can only be added to a log form in log form mode.
                    Create one, then come back to this session's results.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Button as-child>
                    <Link :href="createLogForm()">
                        <ClipboardList />
                        Create a log form
                    </Link>
                </Button>
            </CardContent>
        </Card>

        <form
            v-else-if="sessions.length"
            class="space-y-6"
            @submit.prevent="submit"
        >
            <Card>
                <CardHeader>
                    <CardTitle>Log entry</CardTitle>
                    <CardDescription>
                        Pre-filled from the client and the session times. Check
                        and adjust before adding.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="clinical_log_form_id">Log form</Label>
                        <Select v-model="form.clinical_log_form_id">
                            <SelectTrigger
                                id="clinical_log_form_id"
                                class="w-full"
                                :aria-invalid="
                                    !!form.errors.clinical_log_form_id
                                "
                            >
                                <SelectValue placeholder="Choose a log form" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="logForm in forms"
                                    :key="logForm.id"
                                    :value="String(logForm.id)"
                                >
                                    {{ logForm.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError
                            :message="form.errors.clinical_log_form_id"
                        />
                        <InputError :message="form.errors.date" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="grid gap-2">
                            <Label for="age_group">Age group</Label>
                            <Select v-model="form.age_group">
                                <SelectTrigger id="age_group" class="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in options.ageGroups"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.age_group" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="session_mode">Session mode</Label>
                            <Select v-model="form.session_mode">
                                <SelectTrigger id="session_mode" class="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in options.sessionModes"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.session_mode" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="session_type">Session type</Label>
                            <Select v-model="form.session_type">
                                <SelectTrigger id="session_type" class="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in options.sessionTypes"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.session_type" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="rn_no">RN no.</Label>
                            <Input
                                id="rn_no"
                                v-model="form.rn_no"
                                maxlength="50"
                                :aria-invalid="!!form.errors.rn_no"
                            />
                            <InputError :message="form.errors.rn_no" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="diagnosis">Diagnosis</Label>
                            <Input
                                id="diagnosis"
                                v-model="form.diagnosis"
                                maxlength="255"
                                :aria-invalid="!!form.errors.diagnosis"
                            />
                            <InputError :message="form.errors.diagnosis" />
                        </div>
                    </div>

                    <div class="grid max-w-xs grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="hours">Hours</Label>
                            <Input
                                id="hours"
                                v-model="form.hours"
                                type="number"
                                min="0"
                                max="24"
                                :aria-invalid="!!form.errors.hours"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="minutes">Minutes</Label>
                            <Input
                                id="minutes"
                                v-model="form.minutes"
                                type="number"
                                min="0"
                                max="59"
                                :aria-invalid="!!form.errors.minutes"
                            />
                        </div>
                    </div>
                    <InputError :message="form.errors.hours" />
                    <InputError :message="form.errors.minutes" />
                </CardContent>
            </Card>

            <div class="flex justify-end gap-2">
                <Button variant="outline" as-child>
                    <Link :href="show(client.id)">Cancel</Link>
                </Button>
                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" />
                    Add to clinical hours
                </Button>
            </div>
        </form>
    </div>
</template>
