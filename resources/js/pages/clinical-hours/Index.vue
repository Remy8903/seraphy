<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ClipboardList, Download, Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import {
    create,
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/ClinicalLogFormController';
import { show as downloadPdf } from '@/actions/App/Http/Controllers/ClinicalLogFormPdfController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
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
import { formatDuration } from '@/lib/clinicalHours';
import type { ClinicalHoursSummary, ClinicalLogFormListItem } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Clinical Hours',
                href: index(),
            },
        ],
    },
});

defineProps<{
    summary: ClinicalHoursSummary;
    forms: ClinicalLogFormListItem[];
}>();

const formToDelete = ref<ClinicalLogFormListItem | null>(null);

function formTitle(form: ClinicalLogFormListItem): string {
    return form.placement_name || 'Simple entry';
}

function closeDeleteDialog(open: boolean): void {
    if (!open) {
        formToDelete.value = null;
    }
}
</script>

<template>
    <Head title="Clinical Hours" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Clinical Hours Tracker"
                description="Your observation and hands-on hours, totalled from every log form"
            />
            <Button as-child>
                <Link :href="create()">
                    <Plus />
                    New log form
                </Link>
            </Button>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <Card
                v-for="category in summary.categories"
                :key="category.hours_type"
            >
                <CardHeader>
                    <CardTitle>{{ category.label }}</CardTitle>
                    <CardDescription>
                        Target {{ formatDuration(category.target_minutes) }}
                    </CardDescription>
                    <CardAction>
                        <Badge
                            :variant="
                                category.is_completed ? 'default' : 'secondary'
                            "
                        >
                            {{
                                category.is_completed
                                    ? 'Completed'
                                    : 'In Progress'
                            }}
                        </Badge>
                    </CardAction>
                </CardHeader>
                <CardContent class="space-y-3">
                    <p class="text-3xl font-semibold tabular-nums">
                        {{ formatDuration(category.completed_minutes) }}
                        <span
                            class="text-base font-normal text-muted-foreground"
                        >
                            / {{ formatDuration(category.target_minutes) }}
                        </span>
                    </p>
                    <div
                        class="h-2 overflow-hidden rounded-full bg-muted"
                        role="progressbar"
                        :aria-valuenow="category.percentage"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        :aria-label="`${category.label} progress`"
                    >
                        <div
                            class="h-full rounded-full bg-primary transition-all"
                            :style="{
                                width: `${Math.min(category.percentage, 100)}%`,
                            }"
                        />
                    </div>
                    <div
                        class="flex justify-between text-sm text-muted-foreground"
                    >
                        <span>{{ category.percentage }}% complete</span>
                        <span>
                            {{ formatDuration(category.remaining_minutes) }}
                            remaining
                        </span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
            <Card>
                <CardHeader>
                    <CardTitle>Hours by semester</CardTitle>
                    <CardDescription>
                        Latihan Industri forms count under Internship
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b text-left text-xs text-muted-foreground"
                            >
                                <th class="py-2 pr-2 font-medium">Semester</th>
                                <th
                                    v-for="category in summary.categories"
                                    :key="category.hours_type"
                                    class="px-2 py-2 text-right font-medium"
                                >
                                    {{ category.label }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="period in summary.periods"
                                :key="period.key"
                                class="border-b last:border-0"
                            >
                                <td class="py-2 pr-2">{{ period.label }}</td>
                                <td
                                    v-for="category in summary.categories"
                                    :key="category.hours_type"
                                    class="px-2 py-2 text-right tabular-nums"
                                    :class="{
                                        'text-muted-foreground':
                                            !period.minutes[
                                                category.hours_type
                                            ],
                                    }"
                                >
                                    {{
                                        period.minutes[category.hours_type]
                                            ? formatDuration(
                                                  period.minutes[
                                                      category.hours_type
                                                  ],
                                              )
                                            : '—'
                                    }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t font-medium">
                                <td class="py-2 pr-2">Total</td>
                                <td
                                    v-for="category in summary.categories"
                                    :key="category.hours_type"
                                    class="px-2 py-2 text-right tabular-nums"
                                >
                                    {{
                                        formatDuration(
                                            summary.totals[category.hours_type],
                                        )
                                    }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Log forms</CardTitle>
                    <CardDescription>
                        Download a form as PDF to get it signed by your
                        supervisor
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="forms.length === 0"
                        class="flex flex-col items-center gap-3 rounded-lg border border-dashed py-10 text-center"
                    >
                        <ClipboardList class="size-8 text-muted-foreground" />
                        <div class="space-y-1">
                            <p class="font-medium">No log forms yet</p>
                            <p class="text-sm text-muted-foreground">
                                Add your first placement to start tracking
                                hours.
                            </p>
                        </div>
                        <Button variant="outline" as-child>
                            <Link :href="create()">
                                <Plus />
                                New log form
                            </Link>
                        </Button>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full min-w-[560px] text-sm">
                            <thead>
                                <tr
                                    class="border-b text-left text-xs text-muted-foreground"
                                >
                                    <th class="py-2 pr-2 font-medium">
                                        Semester
                                    </th>
                                    <th class="px-2 py-2 font-medium">
                                        Placement
                                    </th>
                                    <th class="px-2 py-2 font-medium">Type</th>
                                    <th
                                        class="px-2 py-2 text-right font-medium"
                                    >
                                        Hours
                                    </th>
                                    <th class="py-2 pl-2">
                                        <span class="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="form in forms"
                                    :key="form.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-2 pr-2 whitespace-nowrap">
                                        <div class="font-medium">
                                            Y{{ form.year }} Sem
                                            {{ form.semester }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ form.session }}
                                        </div>
                                    </td>
                                    <td class="px-2 py-2">
                                        <div class="font-medium">
                                            {{ formTitle(form) }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{
                                                [
                                                    form.practicum_type,
                                                    form.supervisor_name,
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · ')
                                            }}
                                        </div>
                                    </td>
                                    <td class="px-2 py-2">
                                        <Badge variant="outline">
                                            {{ form.hours_type_label }}
                                        </Badge>
                                    </td>
                                    <td
                                        class="px-2 py-2 text-right whitespace-nowrap tabular-nums"
                                    >
                                        <div class="font-medium">
                                            {{
                                                formatDuration(
                                                    form.total_minutes,
                                                )
                                            }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            <template
                                                v-if="form.mode === 'simple'"
                                            >
                                                Keyed in
                                            </template>
                                            <template v-else>
                                                {{ form.entries_count }}
                                                {{
                                                    form.entries_count === 1
                                                        ? 'session'
                                                        : 'sessions'
                                                }}
                                            </template>
                                        </div>
                                    </td>
                                    <td class="py-2 pl-2">
                                        <div class="flex justify-end gap-1">
                                            <Button
                                                v-if="form.mode === 'log_form'"
                                                variant="ghost"
                                                size="icon"
                                                as-child
                                            >
                                                <a
                                                    :href="
                                                        downloadPdf.url(form.id)
                                                    "
                                                    :aria-label="`Download PDF for ${formTitle(form)}`"
                                                >
                                                    <Download />
                                                </a>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                as-child
                                            >
                                                <Link
                                                    :href="edit(form.id)"
                                                    :aria-label="`Edit ${formTitle(form)}`"
                                                >
                                                    <Pencil />
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                :aria-label="`Delete ${formTitle(form)}`"
                                                @click="formToDelete = form"
                                            >
                                                <Trash2 />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Dialog :open="formToDelete !== null" @update:open="closeDeleteDialog">
            <DialogContent v-if="formToDelete">
                <Form
                    v-bind="destroy.form(formToDelete.id)"
                    :options="{ preserveScroll: true }"
                    class="space-y-6"
                    @success="formToDelete = null"
                    v-slot="{ processing }"
                >
                    <DialogHeader class="space-y-3">
                        <DialogTitle>Delete this log form?</DialogTitle>
                        <DialogDescription>
                            {{ formTitle(formToDelete) }} (Y{{
                                formToDelete.year
                            }}
                            Sem {{ formToDelete.semester }},
                            {{ formToDelete.hours_type_label }}) and its
                            {{ formatDuration(formToDelete.total_minutes) }}
                            will be removed from your tracker. This cannot be
                            undone.
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
                            Delete log form
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
