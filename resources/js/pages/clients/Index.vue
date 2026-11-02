<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Archive, Plus, Users } from '@lucide/vue';
import {
    create,
    index,
    show,
} from '@/actions/App/Http/Controllers/ClientController';
import Heading from '@/components/Heading.vue';
import PrivacyNotice from '@/components/therapy/PrivacyNotice.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { ClientListItem } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Clients', href: index() }],
    },
});

defineProps<{
    clients: ClientListItem[];
    showingArchived: boolean;
}>();

function lastPlayed(date: string | null): string {
    return date
        ? new Date(date).toLocaleDateString(undefined, {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          })
        : 'Not played yet';
}
</script>

<template>
    <Head title="Clients" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="showingArchived ? 'Archived clients' : 'Clients'"
                description="Assign therapy games to your clients and follow their progress"
            />
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link
                        :href="
                            index({
                                query: showingArchived ? {} : { archived: 1 },
                            })
                        "
                    >
                        <Archive />
                        {{ showingArchived ? 'Active clients' : 'Archived' }}
                    </Link>
                </Button>
                <Button as-child>
                    <Link :href="create()">
                        <Plus />
                        New client
                    </Link>
                </Button>
            </div>
        </div>

        <PrivacyNotice />

        <Card>
            <CardContent>
                <div
                    v-if="clients.length === 0"
                    class="flex flex-col items-center gap-3 py-10 text-center"
                >
                    <Users class="size-8 text-muted-foreground" />
                    <div class="space-y-1">
                        <p class="font-medium">
                            {{
                                showingArchived
                                    ? 'No archived clients'
                                    : 'No clients yet'
                            }}
                        </p>
                        <p
                            v-if="!showingArchived"
                            class="text-sm text-muted-foreground"
                        >
                            Add a client by reference to start assigning games.
                        </p>
                    </div>
                    <Button v-if="!showingArchived" variant="outline" as-child>
                        <Link :href="create()">
                            <Plus />
                            New client
                        </Link>
                    </Button>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[560px] text-sm">
                        <thead>
                            <tr
                                class="border-b text-left text-xs text-muted-foreground"
                            >
                                <th class="py-2 pr-2 font-medium">Reference</th>
                                <th class="px-2 py-2 font-medium">Age group</th>
                                <th class="px-2 py-2 font-medium">Language</th>
                                <th class="px-2 py-2 text-right font-medium">
                                    Activities
                                </th>
                                <th class="py-2 pl-2 text-right font-medium">
                                    Last played
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="client in clients"
                                :key="client.id"
                                class="border-b last:border-0 hover:bg-muted/50"
                            >
                                <td class="py-2 pr-2">
                                    <Link
                                        :href="show(client.id)"
                                        class="font-medium underline-offset-4 hover:underline"
                                    >
                                        {{ client.reference_code }}
                                    </Link>
                                    <div
                                        v-if="client.diagnosis"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ client.diagnosis }}
                                    </div>
                                </td>
                                <td class="px-2 py-2">
                                    {{ client.age_group }}
                                </td>
                                <td class="px-2 py-2">
                                    <Badge variant="outline">
                                        {{ client.language }}
                                    </Badge>
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ client.activities_count }}
                                </td>
                                <td
                                    class="py-2 pl-2 text-right whitespace-nowrap text-muted-foreground"
                                >
                                    {{ lastPlayed(client.last_played_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
