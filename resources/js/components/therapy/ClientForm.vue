<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PrivacyNotice from '@/components/therapy/PrivacyNotice.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import type { ClientFormOptions, ClientInput } from '@/types';

const props = defineProps<{
    options: ClientFormOptions;
    initialValues: ClientInput;
    action: { url: string; method: 'post' | 'put' };
    cancelHref: string;
    submitLabel: string;
    requiresConfirmation?: boolean;
}>();

const form = useForm({
    ...props.initialValues,
    confirms_no_personal_data: false,
});

function submit(): void {
    form.submit(props.action.method, props.action.url);
}
</script>

<template>
    <form class="max-w-2xl space-y-6" @submit.prevent="submit">
        <PrivacyNotice />

        <Card>
            <CardContent class="grid gap-5">
                <div class="grid gap-2">
                    <Label for="reference_code">Reference</Label>
                    <Input
                        id="reference_code"
                        v-model="form.reference_code"
                        placeholder="e.g. C-01 or RN no."
                        maxlength="30"
                        autocomplete="off"
                        :aria-invalid="!!form.errors.reference_code"
                    />
                    <p class="text-xs text-muted-foreground">
                        Letters, numbers, spaces, dashes and slashes only. Never
                        the patient's name.
                    </p>
                    <InputError :message="form.errors.reference_code" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="age_group">Age group</Label>
                        <Select v-model="form.age_group">
                            <SelectTrigger
                                id="age_group"
                                class="w-full"
                                :aria-invalid="!!form.errors.age_group"
                            >
                                <SelectValue placeholder="Select" />
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
                        <Label for="language">Therapy language</Label>
                        <Select v-model="form.language">
                            <SelectTrigger
                                id="language"
                                class="w-full"
                                :aria-invalid="!!form.errors.language"
                            >
                                <SelectValue placeholder="Select" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in options.languages"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.language" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="diagnosis">
                        Diagnosis
                        <span class="font-normal text-muted-foreground">
                            (optional)
                        </span>
                    </Label>
                    <Input
                        id="diagnosis"
                        v-model="form.diagnosis"
                        placeholder="e.g. Speech sound disorder"
                        :aria-invalid="!!form.errors.diagnosis"
                    />
                    <InputError :message="form.errors.diagnosis" />
                </div>

                <div class="grid gap-2">
                    <Label for="notes">
                        Notes
                        <span class="font-normal text-muted-foreground">
                            (optional)
                        </span>
                    </Label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        maxlength="2000"
                        placeholder="Therapy focus, what motivates the child…"
                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive md:text-sm dark:bg-input/30"
                        :aria-invalid="!!form.errors.notes"
                    />
                    <InputError :message="form.errors.notes" />
                </div>

                <div v-if="requiresConfirmation" class="grid gap-2">
                    <Label
                        class="flex items-start gap-3 rounded-md border p-3 font-normal"
                    >
                        <Checkbox
                            v-model="form.confirms_no_personal_data"
                            class="mt-0.5"
                        />
                        <span>
                            I confirm these details contain no information that
                            identifies the patient.
                        </span>
                    </Label>
                    <InputError
                        :message="form.errors.confirms_no_personal_data"
                    />
                </div>
            </CardContent>
        </Card>

        <div class="flex items-center justify-end gap-2">
            <Button variant="ghost" as-child>
                <Link :href="cancelHref">Cancel</Link>
            </Button>
            <Button type="submit" :disabled="form.processing">
                <Spinner v-if="form.processing" />
                {{ submitLabel }}
            </Button>
        </div>
    </form>
</template>
