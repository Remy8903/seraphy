<script setup lang="ts">
import { Form, useForm } from '@inertiajs/vue3';
import { Pause, Pencil, Play, Plus, Target, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import {
    destroy,
    store,
    update,
} from '@/actions/App/Http/Controllers/TherapyGoalController';
import TherapyGoalStatusController from '@/actions/App/Http/Controllers/TherapyGoalStatusController';
import InputError from '@/components/InputError.vue';
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
import { Spinner } from '@/components/ui/spinner';
import type { GoalOptions, GoalStatusValue, TherapyGoal } from '@/types';

const props = defineProps<{
    clientId: number;
    goals: TherapyGoal[];
    options: GoalOptions;
}>();

/** Select inputs cannot hold an empty value, so "any" stands for no filter. */
const anyValue = 'any';

const statusBadges: Record<
    GoalStatusValue,
    'default' | 'secondary' | 'outline'
> = {
    active: 'secondary',
    achieved: 'default',
    discontinued: 'outline',
};

type GoalInput = {
    description: string;
    game_type: string;
    language: string;
    target_sound: string;
    word_position: string;
    target_accuracy: number | string;
    consecutive_sessions: number | string;
    min_trials_per_session: number | string;
};

function blankGoal(): GoalInput {
    return {
        description: '',
        game_type: anyValue,
        language: anyValue,
        target_sound: '',
        word_position: anyValue,
        target_accuracy: 80,
        consecutive_sessions: 3,
        min_trials_per_session: 10,
    };
}

const editingGoal = ref<TherapyGoal | null>(null);
const isGoalDialogOpen = ref(false);
const goalToDelete = ref<TherapyGoal | null>(null);
const goalForm = useForm<GoalInput>(blankGoal());

function openGoalDialog(goal: TherapyGoal | null): void {
    editingGoal.value = goal;
    goalForm.defaults(
        goal
            ? {
                  description: goal.description,
                  game_type: goal.game_type ?? anyValue,
                  language: goal.language ?? anyValue,
                  target_sound: goal.target_sound ?? '',
                  word_position: goal.word_position ?? anyValue,
                  target_accuracy: goal.target_accuracy,
                  consecutive_sessions: goal.consecutive_sessions,
                  min_trials_per_session: goal.min_trials_per_session,
              }
            : blankGoal(),
    );
    goalForm.reset();
    goalForm.clearErrors();
    isGoalDialogOpen.value = true;
}

function saveGoal(): void {
    const editing = editingGoal.value;
    const orNull = (value: string): string | null =>
        value === anyValue || value === '' ? null : value;

    goalForm
        .transform((data) => ({
            ...data,
            game_type: orNull(data.game_type),
            language: orNull(data.language),
            target_sound: orNull(data.target_sound.trim()),
            word_position: orNull(data.word_position),
        }))
        .submit(editing ? update(editing.id) : store(props.clientId), {
            preserveScroll: true,
            onSuccess: () => {
                isGoalDialogOpen.value = false;
            },
        });
}

function criteria(goal: TherapyGoal): string {
    return [
        goal.target_sound ? `/${goal.target_sound}/` : null,
        goal.word_position_label,
        goal.language_label,
        goal.game_type_label,
    ]
        .filter(Boolean)
        .join(' · ');
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <Card>
        <CardHeader class="flex flex-row items-start justify-between gap-4">
            <div class="space-y-1.5">
                <CardTitle>Goals</CardTitle>
                <CardDescription>
                    Marked achieved automatically when enough sessions in a row
                    reach the target accuracy.
                </CardDescription>
            </div>
            <Button variant="outline" size="sm" @click="openGoalDialog(null)">
                <Plus />
                Add goal
            </Button>
        </CardHeader>
        <CardContent>
            <p v-if="goals.length === 0" class="text-sm text-muted-foreground">
                No goals yet. Add one to track progress toward a target, e.g.
                80% correct on /s/ at the start of words across 3 sessions.
            </p>

            <ul v-else class="divide-y">
                <li
                    v-for="goal in goals"
                    :key="goal.id"
                    :class="[
                        'flex flex-wrap items-start gap-3 py-3 first:pt-0 last:pb-0',
                        { 'opacity-60': goal.status === 'discontinued' },
                    ]"
                >
                    <Target class="mt-0.5 size-4 shrink-0 text-primary" />
                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-medium">{{ goal.description }}</p>
                            <Badge :variant="statusBadges[goal.status]">
                                {{ goal.status_label }}
                            </Badge>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            <template v-if="criteria(goal)">
                                {{ criteria(goal) }} ·
                            </template>
                            ≥ {{ goal.target_accuracy }}% in
                            {{ goal.consecutive_sessions }}
                            {{
                                goal.consecutive_sessions === 1
                                    ? 'session'
                                    : 'sessions in a row'
                            }}
                            · min {{ goal.min_trials_per_session }} answers
                        </p>

                        <p
                            v-if="
                                goal.status === 'achieved' && goal.achieved_at
                            "
                            class="text-sm font-medium text-emerald-600"
                        >
                            🏆 Achieved {{ formatDate(goal.achieved_at) }}
                        </p>
                        <div
                            v-else-if="goal.status === 'active'"
                            class="flex flex-wrap items-center gap-2 text-sm"
                        >
                            <span class="tabular-nums">
                                {{ goal.streak }} of
                                {{ goal.consecutive_sessions }}
                                {{
                                    goal.consecutive_sessions === 1
                                        ? 'session'
                                        : 'sessions'
                                }}
                                at ≥ {{ goal.target_accuracy }}%
                            </span>
                            <span
                                v-for="session in goal.recent_sessions"
                                :key="session.play_session_id"
                                :title="`${formatDate(session.date)}: ${session.accuracy}% of ${session.trials} answers`"
                                :class="[
                                    'rounded px-1.5 py-0.5 text-xs font-medium tabular-nums',
                                    session.met
                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200'
                                        : 'bg-muted text-muted-foreground',
                                ]"
                            >
                                {{ session.accuracy }}%
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-1">
                        <Form
                            v-if="goal.status !== 'achieved'"
                            v-bind="TherapyGoalStatusController.form(goal.id)"
                            :options="{ preserveScroll: true }"
                            v-slot="{ processing }"
                        >
                            <Button
                                type="submit"
                                variant="ghost"
                                size="icon"
                                :disabled="processing"
                                :aria-label="
                                    goal.status === 'active'
                                        ? `Discontinue goal: ${goal.description}`
                                        : `Resume goal: ${goal.description}`
                                "
                                :title="
                                    goal.status === 'active'
                                        ? 'Discontinue'
                                        : 'Resume'
                                "
                            >
                                <Pause v-if="goal.status === 'active'" />
                                <Play v-else />
                            </Button>
                        </Form>
                        <Button
                            variant="ghost"
                            size="icon"
                            :aria-label="`Edit goal: ${goal.description}`"
                            title="Edit"
                            @click="openGoalDialog(goal)"
                        >
                            <Pencil />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            :aria-label="`Delete goal: ${goal.description}`"
                            title="Delete"
                            @click="goalToDelete = goal"
                        >
                            <Trash2 />
                        </Button>
                    </div>
                </li>
            </ul>
        </CardContent>

        <Dialog v-model:open="isGoalDialogOpen">
            <DialogContent class="max-h-[90vh] overflow-y-auto">
                <form class="space-y-5" @submit.prevent="saveGoal">
                    <DialogHeader>
                        <DialogTitle>
                            {{ editingGoal ? 'Edit goal' : 'Add goal' }}
                        </DialogTitle>
                        <DialogDescription>
                            Only answers that match the filters count toward the
                            goal. Leave a filter on “Any” to count every answer.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="goal_description">Goal</Label>
                        <Input
                            id="goal_description"
                            v-model="goalForm.description"
                            maxlength="255"
                            placeholder="e.g. Produce /s/ at the start of words"
                            :aria-invalid="!!goalForm.errors.description"
                        />
                        <InputError :message="goalForm.errors.description" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="goal_target_sound">Target sound</Label>
                            <Input
                                id="goal_target_sound"
                                v-model="goalForm.target_sound"
                                maxlength="10"
                                placeholder="Any, or e.g. s"
                                :aria-invalid="!!goalForm.errors.target_sound"
                            />
                            <InputError
                                :message="goalForm.errors.target_sound"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="goal_word_position">Position</Label>
                            <Select v-model="goalForm.word_position">
                                <SelectTrigger
                                    id="goal_word_position"
                                    class="w-full"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="anyValue">
                                        Any
                                    </SelectItem>
                                    <SelectItem
                                        v-for="option in options.wordPositions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError
                                :message="goalForm.errors.word_position"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="goal_language">Language</Label>
                            <Select v-model="goalForm.language">
                                <SelectTrigger
                                    id="goal_language"
                                    class="w-full"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="anyValue">
                                        Any
                                    </SelectItem>
                                    <SelectItem
                                        v-for="option in options.languages"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="goalForm.errors.language" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="goal_game_type">Game</Label>
                            <Select v-model="goalForm.game_type">
                                <SelectTrigger
                                    id="goal_game_type"
                                    class="w-full"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="anyValue">
                                        Any
                                    </SelectItem>
                                    <SelectItem
                                        v-for="option in options.gameTypes"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="goalForm.errors.game_type" />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="grid gap-2">
                            <Label for="goal_target_accuracy">Target %</Label>
                            <Input
                                id="goal_target_accuracy"
                                v-model="goalForm.target_accuracy"
                                type="number"
                                min="1"
                                max="100"
                                :aria-invalid="
                                    !!goalForm.errors.target_accuracy
                                "
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="goal_consecutive_sessions">
                                Sessions in a row
                            </Label>
                            <Input
                                id="goal_consecutive_sessions"
                                v-model="goalForm.consecutive_sessions"
                                type="number"
                                min="1"
                                max="10"
                                :aria-invalid="
                                    !!goalForm.errors.consecutive_sessions
                                "
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="goal_min_trials">Min answers</Label>
                            <Input
                                id="goal_min_trials"
                                v-model="goalForm.min_trials_per_session"
                                type="number"
                                min="1"
                                max="100"
                                :aria-invalid="
                                    !!goalForm.errors.min_trials_per_session
                                "
                            />
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        A session with fewer matching answers than the minimum
                        does not count. Prompted answers count as not correct.
                    </p>
                    <InputError :message="goalForm.errors.target_accuracy" />
                    <InputError
                        :message="goalForm.errors.consecutive_sessions"
                    />
                    <InputError
                        :message="goalForm.errors.min_trials_per_session"
                    />

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button type="button" variant="secondary">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button type="submit" :disabled="goalForm.processing">
                            <Spinner v-if="goalForm.processing" />
                            {{ editingGoal ? 'Save changes' : 'Add goal' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="goalToDelete !== null"
            @update:open="(open: boolean) => !open && (goalToDelete = null)"
        >
            <DialogContent v-if="goalToDelete">
                <Form
                    v-bind="destroy.form(goalToDelete.id)"
                    :options="{ preserveScroll: true }"
                    class="space-y-6"
                    @success="goalToDelete = null"
                    v-slot="{ processing }"
                >
                    <DialogHeader class="space-y-3">
                        <DialogTitle>Delete this goal?</DialogTitle>
                        <DialogDescription>
                            “{{ goalToDelete.description }}” will be removed.
                            Recorded answers are kept. Discontinue the goal
                            instead to keep it on the client's record.
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
                            Delete goal
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </Card>
</template>
