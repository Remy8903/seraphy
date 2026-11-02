<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { BookOpen, Gamepad2, Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import {
    create,
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/ActivityController';
import { index as library } from '@/actions/App/Http/Controllers/ContentItemController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { ActivityListItem } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Activities', href: index() }],
    },
});

defineProps<{
    activities: ActivityListItem[];
}>();

const activityToDelete = ref<ActivityListItem | null>(null);
</script>

<template>
    <Head title="Activities" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Activities"
                description="Games configured with your words, ready to assign to clients"
            />
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="library()">
                        <BookOpen />
                        Word library
                    </Link>
                </Button>
                <Button as-child>
                    <Link :href="create()">
                        <Plus />
                        New activity
                    </Link>
                </Button>
            </div>
        </div>

        <Card>
            <CardContent>
                <div
                    v-if="activities.length === 0"
                    class="flex flex-col items-center gap-3 py-10 text-center"
                >
                    <Gamepad2 class="size-8 text-muted-foreground" />
                    <div class="space-y-1">
                        <p class="font-medium">No activities yet</p>
                        <p class="text-sm text-muted-foreground">
                            Build a sound card drill or minimal pairs game from
                            the word library.
                        </p>
                    </div>
                    <Button variant="outline" as-child>
                        <Link :href="create()">
                            <Plus />
                            New activity
                        </Link>
                    </Button>
                </div>

                <ul v-else class="divide-y">
                    <li
                        v-for="activity in activities"
                        :key="activity.id"
                        class="flex flex-wrap items-center gap-3 py-3"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ activity.title }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ activity.items_count }}
                                {{
                                    activity.game_type === 'minimal_pairs'
                                        ? 'pairs'
                                        : 'words'
                                }}
                                · assigned to {{ activity.clients_count }}
                                {{
                                    activity.clients_count === 1
                                        ? 'client'
                                        : 'clients'
                                }}
                                · {{ activity.play_sessions_count }}
                                {{
                                    activity.play_sessions_count === 1
                                        ? 'session'
                                        : 'sessions'
                                }}
                            </p>
                        </div>
                        <Badge variant="secondary">
                            {{ activity.game_type_label }}
                        </Badge>
                        <Badge variant="outline">{{ activity.language }}</Badge>
                        <div class="flex gap-1">
                            <Button variant="ghost" size="icon" as-child>
                                <Link
                                    :href="edit(activity.id)"
                                    :aria-label="`Edit ${activity.title}`"
                                >
                                    <Pencil />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                :aria-label="`Delete ${activity.title}`"
                                @click="activityToDelete = activity"
                            >
                                <Trash2 />
                            </Button>
                        </div>
                    </li>
                </ul>
            </CardContent>
        </Card>

        <Dialog
            :open="activityToDelete !== null"
            @update:open="(open: boolean) => !open && (activityToDelete = null)"
        >
            <DialogContent v-if="activityToDelete">
                <Form
                    v-bind="destroy.form(activityToDelete.id)"
                    :options="{ preserveScroll: true }"
                    class="space-y-6"
                    @success="activityToDelete = null"
                    v-slot="{ processing }"
                >
                    <DialogHeader class="space-y-3">
                        <DialogTitle>
                            Delete {{ activityToDelete.title }}?
                        </DialogTitle>
                        <DialogDescription>
                            <template
                                v-if="activityToDelete.play_sessions_count"
                            >
                                Its
                                {{ activityToDelete.play_sessions_count }}
                                played
                                {{
                                    activityToDelete.play_sessions_count === 1
                                        ? 'session'
                                        : 'sessions'
                                }}
                                and their results will also be deleted from your
                                clients' progress.
                            </template>
                            <template v-else>
                                It will be removed from every client's plan.
                            </template>
                            This cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="activityToDelete = null"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="processing"
                        >
                            Delete activity
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
