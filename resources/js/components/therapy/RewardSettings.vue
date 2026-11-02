<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import type { StickerSetOption } from '@/types';

defineProps<{
    stickerSets: StickerSetOption[];
    error?: string;
}>();

const rewardEvery = defineModel<number | string | undefined>('rewardEvery');
const stickerSet = defineModel<string | null | undefined>('stickerSet');

const intervals = [
    { value: '0', label: 'Off' },
    { value: '3', label: 'Every 3 answers' },
    { value: '5', label: 'Every 5 answers' },
    { value: '10', label: 'Every 10 answers' },
];

const interval = computed({
    get: () => String(rewardEvery.value ?? 0),
    set: (value: string) => {
        rewardEvery.value = Number(value);
    },
});
const isOn = computed(() => Number(rewardEvery.value ?? 0) > 0);
</script>

<template>
    <div
        class="flex flex-wrap items-start gap-x-6 gap-y-3 rounded-md border p-3"
    >
        <div class="grid gap-2">
            <Label for="reward_every">Sticker rewards</Label>
            <Select v-model="interval">
                <SelectTrigger id="reward_every" class="w-44">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in intervals"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>
        <div v-if="isOn" class="grid gap-2">
            <span class="text-sm font-medium">Sticker set</span>
            <div
                role="radiogroup"
                aria-label="Sticker set"
                class="flex flex-wrap gap-2"
            >
                <button
                    v-for="set in stickerSets"
                    :key="set.value"
                    type="button"
                    role="radio"
                    :aria-checked="stickerSet === set.value"
                    :class="
                        cn(
                            'rounded-md border px-3 py-1.5 text-left text-sm transition-colors',
                            stickerSet === set.value
                                ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                : 'hover:bg-muted/50',
                        )
                    "
                    @click="stickerSet = set.value"
                >
                    <span class="font-medium">{{ set.label }}</span>
                    <span class="ml-2" aria-hidden="true">
                        {{ set.stickers.slice(0, 4).join('') }}
                    </span>
                </button>
            </div>
            <InputError :message="error" />
        </div>
        <p class="basis-full text-xs text-muted-foreground">
            The child picks a sticker for their board. Stickers are just for
            motivation and do not change the results.
        </p>
    </div>
</template>
