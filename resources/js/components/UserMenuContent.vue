<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Settings } from '@lucide/vue';
import { useOfflineSync } from '@/composables/useOfflineSync';
import { clearOfflineData } from '@/lib/offlineStore';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';

type Props = {
    user: User;
};

const { unsyncedCount, sync } = useOfflineSync();

/**
 * Sign out, first trying to sync offline sessions, then removing every offline
 * activity, session and picture from this device.
 */
const handleLogout = async () => {
    router.flushAll();

    try {
        await sync();
        await clearOfflineData();
    } catch {
        // Offline storage may be blocked; signing out still goes ahead.
    }

    router.post(logout());
};

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" :href="edit()" prefetch>
                <Settings class="mr-2 h-4 w-4" />
                Settings
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <button
            type="button"
            class="block w-full cursor-pointer text-left"
            data-test="logout-button"
            @click="handleLogout"
        >
            <LogOut class="mr-2 inline h-4 w-4" />
            Log out
            <span
                v-if="unsyncedCount > 0"
                class="mt-1 block text-xs text-destructive"
            >
                {{ unsyncedCount }} offline
                {{ unsyncedCount === 1 ? 'session is' : 'sessions are' }} not
                synced and will be removed from this device.
            </span>
        </button>
    </DropdownMenuItem>
</template>
