<script setup lang="ts">
import { ImageOff, ImagePlus } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import PrivacyNotice from '@/components/therapy/PrivacyNotice.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    /** The background saved with the activity, if any. */
    currentUrl: string | null;
    error?: string;
}>();

const background = defineModel<File | null | undefined>('background', {
    required: true,
});
const removeBackground = defineModel<boolean | undefined>('removeBackground', {
    required: true,
});

const fileInput = ref<InstanceType<typeof Input> | null>(null);
const previewUrl = ref<string | null>(null);

watch(background, (file) => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }

    previewUrl.value = file ? URL.createObjectURL(file) : null;
});

onBeforeUnmount(() => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
});

/** The picture the game will show once the activity is saved. */
const shownUrl = computed(
    () =>
        previewUrl.value ?? (removeBackground.value ? null : props.currentUrl),
);

function select(event: Event): void {
    const input = event.target as HTMLInputElement;

    background.value = input.files?.[0] ?? null;
    removeBackground.value = false;
}

function clear(): void {
    background.value = null;
    removeBackground.value = props.currentUrl !== null;

    const element = fileInput.value?.$el as HTMLInputElement | undefined;

    if (element) {
        element.value = '';
    }
}
</script>

<template>
    <div class="grid gap-5 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
        <div class="space-y-4">
            <PrivacyNotice context="upload" />
            <div class="grid gap-2">
                <Label for="background">Background picture</Label>
                <Input
                    id="background"
                    ref="fileInput"
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    :aria-invalid="!!error"
                    @change="select"
                />
                <p class="text-xs text-muted-foreground">
                    JPG, PNG or WebP, up to 5 MB. A wide picture works best. It
                    is softened so the game stays easy to see.
                </p>
                <InputError :message="error" />
            </div>
            <Button
                v-if="shownUrl"
                type="button"
                variant="outline"
                size="sm"
                @click="clear"
            >
                <ImageOff />
                {{
                    background ? 'Don’t use this picture' : 'Remove background'
                }}
            </Button>
        </div>

        <div class="space-y-2">
            <p class="text-sm font-medium">Preview</p>
            <div
                class="relative isolate flex aspect-video items-center justify-center overflow-hidden rounded-lg border bg-background"
            >
                <template v-if="shownUrl">
                    <div
                        class="absolute inset-0 -z-10 bg-cover bg-center"
                        :style="{
                            backgroundImage: `url(${JSON.stringify(shownUrl)})`,
                        }"
                    />
                    <div class="absolute inset-0 -z-10 bg-background/40" />
                    <div
                        class="flex size-20 items-center justify-center rounded-2xl border-4 border-border bg-background text-5xl shadow-sm"
                        aria-hidden="true"
                    >
                        🍎
                    </div>
                </template>
                <p
                    v-else
                    class="flex flex-col items-center gap-2 text-sm text-muted-foreground"
                >
                    <ImagePlus class="size-6" />
                    No background. The game uses a plain screen.
                </p>
            </div>
        </div>
    </div>
</template>
