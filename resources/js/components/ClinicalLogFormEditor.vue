<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ClipboardList, Clock, Plus, Trash2 } from '@lucide/vue';
import { computed, onMounted } from 'vue';
import { index } from '@/actions/App/Http/Controllers/ClinicalLogFormController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import { formatDuration } from '@/lib/clinicalHours';
import { edit as editProfile } from '@/routes/profile';
import type {
    ClinicalLogEntryInput,
    ClinicalLogFormInput,
    ClinicalLogFormOptions,
    ClinicalLogMode,
} from '@/types';

const props = defineProps<{
    options: ClinicalLogFormOptions;
    initialValues: ClinicalLogFormInput;
    action: { url: string; method: 'post' | 'put' };
    submitLabel: string;
    remembersMode?: boolean;
}>();

const page = usePage();
const student = computed(() => page.props.auth.user);

const modeStorageKey = 'clinical-hours.mode';

const modes = [
    {
        value: 'log_form',
        Icon: ClipboardList,
        label: 'Log form',
        description: 'Log every session for the PDF form',
    },
    {
        value: 'simple',
        Icon: Clock,
        label: 'Simple',
        description: 'Just key in the total hours',
    },
] as const;

const years = ['1', '2', '3', '4'];
const semesters = ['1', '2'];

const form = useForm<ClinicalLogFormInput>({
    ...props.initialValues,
    year: String(props.initialValues.year),
    semester: String(props.initialValues.semester),
    days: [...props.initialValues.days],
    entries: props.initialValues.entries.map((entry) => ({ ...entry })),
});

function blankEntry(): ClinicalLogEntryInput {
    const previous = form.entries.at(-1);

    return {
        id: null,
        date: '',
        age_group: previous?.age_group ?? '',
        rn_no: '',
        session_mode: previous?.session_mode ?? '',
        session_type: previous?.session_type ?? '',
        diagnosis: '',
        hours: 0,
        minutes: 0,
    };
}

if (form.entries.length === 0) {
    form.entries.push(blankEntry());
}

const isSimple = computed(() => form.mode === 'simple');

const loggedSessionsToRemove = computed(() =>
    isSimple.value && props.initialValues.mode === 'log_form'
        ? props.initialValues.entries.length
        : 0,
);

onMounted(() => {
    if (!props.remembersMode) {
        return;
    }

    try {
        const storedMode = localStorage.getItem(modeStorageKey);

        if (storedMode === 'log_form' || storedMode === 'simple') {
            form.mode = storedMode;
        }
    } catch {
        // Storage can be unavailable (e.g. private browsing), so keep the default mode.
    }
});

function selectMode(mode: ClinicalLogMode): void {
    form.mode = mode;

    if (!props.remembersMode) {
        return;
    }

    try {
        localStorage.setItem(modeStorageKey, mode);
    } catch {
        // Remembering the mode is only a convenience.
    }
}

const sessionsTotalMinutes = computed(() =>
    form.entries.reduce(
        (sum, entry) =>
            sum + Number(entry.hours || 0) * 60 + Number(entry.minutes || 0),
        0,
    ),
);

const simpleTotalMinutes = computed(
    () => Number(form.hours || 0) * 60 + Number(form.minutes || 0),
);

const sessionEndYear = computed(() => {
    const start = Number(form.session_start_year);

    return start ? start + 1 : '____';
});

function toggleDay(day: string, checked: boolean | 'indeterminate'): void {
    form.days = checked
        ? [...form.days, day]
        : form.days.filter((selected) => selected !== day);
}

function addEntry(): void {
    form.entries.push(blankEntry());
}

function removeEntry(index: number): void {
    form.entries.splice(index, 1);
}

function entryError(
    index: number,
    field: keyof ClinicalLogEntryInput,
): string | undefined {
    return (form.errors as Record<string, string | undefined>)[
        `entries.${index}.${field}`
    ];
}

function entryHasErrors(index: number): boolean {
    return Object.keys(form.errors).some((key) =>
        key.startsWith(`entries.${index}.`),
    );
}

function submit(): void {
    form.submit(props.action.method, props.action.url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="space-y-2">
            <div
                role="radiogroup"
                aria-label="Entry mode"
                class="inline-flex gap-1 rounded-lg bg-muted p-1"
            >
                <button
                    v-for="{ value, Icon, label, description } in modes"
                    :key="value"
                    type="button"
                    role="radio"
                    :aria-checked="form.mode === value"
                    :title="description"
                    :class="[
                        'flex items-center gap-1.5 rounded-md px-3.5 py-1.5 text-sm transition-colors',
                        form.mode === value
                            ? 'bg-background font-medium shadow-xs'
                            : 'text-muted-foreground hover:bg-background/60 hover:text-foreground',
                    ]"
                    @click="selectMode(value)"
                >
                    <component :is="Icon" class="size-4" />
                    {{ label }}
                </button>
            </div>
            <p class="text-sm text-muted-foreground">
                {{
                    isSimple
                        ? 'Key in your total hours only. Simple entries count toward your tracker but have no PDF.'
                        : 'Record each session so you can download the PDF for your supervisor to sign.'
                }}
            </p>
            <p
                v-if="loggedSessionsToRemove > 0"
                class="text-sm text-destructive"
            >
                Saving in simple mode will remove the
                {{ loggedSessionsToRemove }} logged
                {{ loggedSessionsToRemove === 1 ? 'session' : 'sessions' }}
                on this form.
            </p>
            <InputError :message="form.errors.mode" />
        </div>

        <div
            class="grid gap-6"
            :class="isSimple ? 'xl:grid-cols-2' : 'xl:grid-cols-3'"
        >
            <Card>
                <CardHeader>
                    <CardTitle>Student clinician</CardTitle>
                    <CardDescription>
                        Name and matric no. come from your
                        <Link
                            :href="editProfile()"
                            class="underline underline-offset-4"
                            >profile</Link
                        >.
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label>Name</Label>
                            <p class="text-sm font-medium">
                                {{ student.name }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label>Matric no.</Label>
                            <p
                                v-if="student.matric_no"
                                class="text-sm font-medium"
                            >
                                {{ student.matric_no }}
                            </p>
                            <Link
                                v-else
                                :href="editProfile()"
                                class="text-sm text-muted-foreground underline underline-offset-4"
                            >
                                Add matric no.
                            </Link>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="year">Year</Label>
                            <Select v-model="form.year">
                                <SelectTrigger
                                    id="year"
                                    class="w-full"
                                    :aria-invalid="!!form.errors.year"
                                >
                                    <SelectValue placeholder="Select year" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="year in years"
                                        :key="year"
                                        :value="year"
                                    >
                                        Year {{ year }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.year" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="semester">Semester</Label>
                            <Select v-model="form.semester">
                                <SelectTrigger
                                    id="semester"
                                    class="w-full"
                                    :aria-invalid="!!form.errors.semester"
                                >
                                    <SelectValue
                                        placeholder="Select semester"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="semester in semesters"
                                        :key="semester"
                                        :value="semester"
                                    >
                                        Semester {{ semester }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.semester" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="session_start_year">Session</Label>
                        <div class="flex items-center gap-2">
                            <Input
                                id="session_start_year"
                                v-model="form.session_start_year"
                                type="number"
                                min="2000"
                                max="2100"
                                class="w-28"
                                placeholder="2025"
                                :aria-invalid="!!form.errors.session_start_year"
                            />
                            <span class="text-sm text-muted-foreground">
                                / {{ sessionEndYear }}
                            </span>
                        </div>
                        <InputError :message="form.errors.session_start_year" />
                    </div>
                </CardContent>
            </Card>

            <Card v-if="!isSimple">
                <CardHeader>
                    <CardTitle>Placement</CardTitle>
                    <CardDescription>
                        Where and when the sessions took place
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <div class="grid gap-2">
                        <Label>Day</Label>
                        <div class="flex flex-wrap gap-x-4 gap-y-2">
                            <Label
                                v-for="day in options.days"
                                :key="day.value"
                                class="flex items-center gap-2 font-normal"
                            >
                                <Checkbox
                                    :model-value="form.days.includes(day.value)"
                                    @update:model-value="
                                        toggleDay(day.value, $event)
                                    "
                                />
                                {{ day.label }}
                            </Label>
                        </div>
                        <InputError :message="form.errors.days" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="placement">Placement</Label>
                            <Select v-model="form.placement">
                                <SelectTrigger
                                    id="placement"
                                    class="w-full"
                                    :aria-invalid="!!form.errors.placement"
                                >
                                    <SelectValue placeholder="Select" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in options.placements"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.placement" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="clinic_mode">Clinic mode</Label>
                            <Select v-model="form.clinic_mode">
                                <SelectTrigger
                                    id="clinic_mode"
                                    class="w-full"
                                    :aria-invalid="!!form.errors.clinic_mode"
                                >
                                    <SelectValue placeholder="Select" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in options.clinicModes"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.clinic_mode" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="placement_name">Name of placement</Label>
                        <Input
                            id="placement_name"
                            v-model="form.placement_name"
                            placeholder="e.g. Klinik Pertuturan UKM"
                            :aria-invalid="!!form.errors.placement_name"
                        />
                        <InputError :message="form.errors.placement_name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="supervisor_name">Name of supervisor</Label>
                        <Input
                            id="supervisor_name"
                            v-model="form.supervisor_name"
                            :aria-invalid="!!form.errors.supervisor_name"
                        />
                        <InputError :message="form.errors.supervisor_name" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Clinical hours information</CardTitle>
                    <CardDescription>
                        Hours type decides which target these hours count toward
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="practicum_type"
                            >Type of clinical practicum</Label
                        >
                        <Select v-model="form.practicum_type">
                            <SelectTrigger
                                id="practicum_type"
                                class="w-full"
                                :aria-invalid="!!form.errors.practicum_type"
                            >
                                <SelectValue placeholder="Select" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in options.practicumTypes"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.practicum_type" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="hours_type">Hours type</Label>
                        <Select v-model="form.hours_type">
                            <SelectTrigger
                                id="hours_type"
                                class="w-full"
                                :aria-invalid="!!form.errors.hours_type"
                            >
                                <SelectValue placeholder="Select" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in options.hoursTypes"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.hours_type" />
                    </div>

                    <template v-if="isSimple">
                        <div class="grid gap-2">
                            <Label for="simple_placement_name">
                                Placement
                                <span class="font-normal text-muted-foreground">
                                    (optional)
                                </span>
                            </Label>
                            <Input
                                id="simple_placement_name"
                                v-model="form.placement_name"
                                placeholder="e.g. Klinik Pertuturan UKM"
                                :aria-invalid="!!form.errors.placement_name"
                            />
                            <InputError :message="form.errors.placement_name" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="simple_hours">Hours</Label>
                                <Input
                                    id="simple_hours"
                                    v-model="form.hours"
                                    type="number"
                                    min="0"
                                    max="9999"
                                    :aria-invalid="!!form.errors.hours"
                                />
                                <InputError :message="form.errors.hours" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="simple_minutes">Minutes</Label>
                                <Input
                                    id="simple_minutes"
                                    v-model="form.minutes"
                                    type="number"
                                    min="0"
                                    max="59"
                                    step="5"
                                    :aria-invalid="!!form.errors.minutes"
                                />
                                <InputError :message="form.errors.minutes" />
                            </div>
                        </div>

                        <p class="text-sm text-muted-foreground">
                            Total
                            <span class="font-medium text-foreground">
                                {{ formatDuration(simpleTotalMinutes) }}
                            </span>
                        </p>
                    </template>
                </CardContent>
            </Card>
        </div>

        <Card v-if="!isSimple">
            <CardHeader>
                <CardTitle>Sessions</CardTitle>
                <CardDescription>
                    One row per session. The PDF prints 25 rows per page and
                    leaves the signature column for your supervisor.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1080px] text-sm">
                        <thead>
                            <tr
                                class="border-b text-left text-xs text-muted-foreground"
                            >
                                <th class="w-10 py-2 pr-2 font-medium">No.</th>
                                <th class="w-40 px-2 py-2 font-medium">Date</th>
                                <th class="w-28 px-2 py-2 font-medium">
                                    Age group
                                </th>
                                <th class="w-28 px-2 py-2 font-medium">
                                    RN No.
                                </th>
                                <th class="w-36 px-2 py-2 font-medium">Mode</th>
                                <th class="w-32 px-2 py-2 font-medium">Type</th>
                                <th class="px-2 py-2 font-medium">
                                    Disorders / diagnosis
                                </th>
                                <th class="w-20 px-2 py-2 font-medium">
                                    Hours
                                </th>
                                <th class="w-20 px-2 py-2 font-medium">Mins</th>
                                <th class="w-10 py-2 pl-2">
                                    <span class="sr-only">Remove</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <template
                                v-for="(entry, index) in form.entries"
                                :key="index"
                            >
                                <tr class="align-top">
                                    <td
                                        class="py-2 pt-4 pr-2 text-muted-foreground"
                                    >
                                        {{ index + 1 }}
                                    </td>
                                    <td class="px-2 py-2">
                                        <Input
                                            v-model="entry.date"
                                            type="date"
                                            :aria-label="`Session ${index + 1} date`"
                                            :aria-invalid="
                                                !!entryError(index, 'date')
                                            "
                                        />
                                    </td>
                                    <td class="px-2 py-2">
                                        <Select v-model="entry.age_group">
                                            <SelectTrigger
                                                class="w-full"
                                                :aria-label="`Session ${index + 1} age group`"
                                                :aria-invalid="
                                                    !!entryError(
                                                        index,
                                                        'age_group',
                                                    )
                                                "
                                            >
                                                <SelectValue placeholder="—" />
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
                                    </td>
                                    <td class="px-2 py-2">
                                        <Input
                                            v-model="entry.rn_no"
                                            :aria-label="`Session ${index + 1} RN number`"
                                            :aria-invalid="
                                                !!entryError(index, 'rn_no')
                                            "
                                        />
                                    </td>
                                    <td class="px-2 py-2">
                                        <Select v-model="entry.session_mode">
                                            <SelectTrigger
                                                class="w-full"
                                                :aria-label="`Session ${index + 1} mode`"
                                                :aria-invalid="
                                                    !!entryError(
                                                        index,
                                                        'session_mode',
                                                    )
                                                "
                                            >
                                                <SelectValue placeholder="—" />
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
                                    </td>
                                    <td class="px-2 py-2">
                                        <Select v-model="entry.session_type">
                                            <SelectTrigger
                                                class="w-full"
                                                :aria-label="`Session ${index + 1} type`"
                                                :aria-invalid="
                                                    !!entryError(
                                                        index,
                                                        'session_type',
                                                    )
                                                "
                                            >
                                                <SelectValue placeholder="—" />
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
                                    </td>
                                    <td class="px-2 py-2">
                                        <Input
                                            v-model="entry.diagnosis"
                                            :aria-label="`Session ${index + 1} disorders or diagnosis`"
                                            :aria-invalid="
                                                !!entryError(index, 'diagnosis')
                                            "
                                        />
                                    </td>
                                    <td class="px-2 py-2">
                                        <Input
                                            v-model="entry.hours"
                                            type="number"
                                            min="0"
                                            max="24"
                                            :aria-label="`Session ${index + 1} hours`"
                                            :aria-invalid="
                                                !!entryError(index, 'hours')
                                            "
                                        />
                                    </td>
                                    <td class="px-2 py-2">
                                        <Input
                                            v-model="entry.minutes"
                                            type="number"
                                            min="0"
                                            max="59"
                                            step="5"
                                            :aria-label="`Session ${index + 1} minutes`"
                                            :aria-invalid="
                                                !!entryError(index, 'minutes')
                                            "
                                        />
                                    </td>
                                    <td class="py-2 pl-2">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            :disabled="
                                                form.entries.length === 1
                                            "
                                            :aria-label="`Remove session ${index + 1}`"
                                            @click="removeEntry(index)"
                                        >
                                            <Trash2 />
                                        </Button>
                                    </td>
                                </tr>
                                <tr v-if="entryHasErrors(index)">
                                    <td></td>
                                    <td colspan="9" class="px-2 pb-2">
                                        <InputError
                                            v-for="field in [
                                                'date',
                                                'age_group',
                                                'rn_no',
                                                'session_mode',
                                                'session_type',
                                                'diagnosis',
                                                'hours',
                                                'minutes',
                                            ] as const"
                                            :key="field"
                                            :message="entryError(index, field)"
                                        />
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="border-t font-medium">
                                <td colspan="7" class="px-2 py-3 text-right">
                                    Total
                                </td>
                                <td colspan="3" class="px-2 py-3">
                                    {{ formatDuration(sessionsTotalMinutes) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <InputError :message="form.errors.entries" />

                <Button type="button" variant="outline" @click="addEntry">
                    <Plus />
                    Add session
                </Button>
            </CardContent>
        </Card>

        <div class="flex items-center justify-end gap-2">
            <Button variant="ghost" as-child>
                <Link :href="index()">Cancel</Link>
            </Button>
            <Button type="submit" :disabled="form.processing">
                <Spinner v-if="form.processing" />
                {{ submitLabel }}
            </Button>
        </div>
    </form>
</template>
