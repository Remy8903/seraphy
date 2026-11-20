<script setup lang="ts">
import { computed } from 'vue';
import TherapyIllustration from '@/components/therapy/TherapyIllustration.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    illustrationOptions,
    resolveIllustration,
} from '@/lib/therapyIllustrations';

defineProps<{ id: string; invalid?: boolean }>();

const model = defineModel<string>({ required: true });
const none = 'no-illustration';
const selection = computed({
    get: () => model.value || none,
    set: (value: string) => {
        model.value = value === none ? '' : value;
    },
});
const isCustom = computed(
    () =>
        model.value !== '' &&
        !illustrationOptions.some((item) => item.symbol === model.value),
);
</script>

<template>
    <div class="flex flex-col gap-2">
        <Select v-model="selection">
            <SelectTrigger :id="id" :aria-invalid="invalid" class="w-full">
                <SelectValue placeholder="Choose an illustration" />
            </SelectTrigger>
            <SelectContent class="max-h-64">
                <SelectItem :value="none">Upload a picture instead</SelectItem>
                <SelectItem v-if="isCustom" :value="model">
                    Existing picture reference
                </SelectItem>
                <SelectItem
                    v-for="item in illustrationOptions"
                    :key="item.symbol"
                    :value="item.symbol"
                >
                    {{ item.label }}
                </SelectItem>
            </SelectContent>
        </Select>
        <div
            v-if="model"
            class="flex items-center gap-2 rounded-xl bg-brand-soft/60 px-3 py-2 dark:bg-brand-indigo/15"
        >
            <TherapyIllustration :symbol="model" class="size-12 shrink-0" />
            <span class="text-xs text-muted-foreground">
                {{
                    resolveIllustration(model).isKnown
                        ? 'Original artwork from Pip’s world'
                        : 'Upload a matching picture for this custom item.'
                }}
            </span>
        </div>
    </div>
</template>
