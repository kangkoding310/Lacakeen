<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import type { NotificationItem } from '@/types/notification';
import type { Paginated } from '@/types/pagination';
import { Head, Link, router } from '@inertiajs/vue3';
import { Bell, CheckCheck, CircleCheck, Clock3 } from 'lucide-vue-next';

defineProps<{ notifications: Paginated<NotificationItem> }>();
const read = (notification: NotificationItem) =>
    router.patch(route('notifications.read', notification.id), {}, { preserveScroll: true });
const format = (date: string) =>
    new Intl.RelativeTimeFormat('en', { numeric: 'auto' }).format(
        Math.round((new Date(date).getTime() - Date.now()) / 86400000),
        'day'
    );
</script>
<template>
    <Head title="Notifications" />
    <AppLayout title="Notifications" section="Tools"
        ><div class="page-shell max-w-5xl">
            <div class="flex items-end justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Notifications</h1>
                    <p class="mt-1 text-sm text-slate-500">Updates that need your attention.</p>
                </div>
                <button
                    class="ui-button-secondary"
                    @click="router.patch(route('notifications.read-all'))"
                >
                    <CheckCheck class="h-4 w-4" />Mark all read
                </button>
            </div>
            <div
                v-if="notifications.data.length"
                class="ui-card mt-6 divide-y divide-slate-100 overflow-hidden"
            >
                <article
                    v-for="notification in notifications.data"
                    :key="notification.id"
                    class="flex gap-4 p-4 sm:p-5"
                    :class="!notification.read_at && 'bg-blue-50/40'"
                >
                    <div
                        class="grid h-10 w-10 shrink-0 place-items-center rounded-xl"
                        :class="
                            notification.read_at
                                ? 'bg-slate-100 text-slate-400'
                                : 'bg-blue-100 text-blue-600'
                        "
                    >
                        <Bell class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <Link
                            :href="notification.data.url || '#'"
                            class="text-sm font-semibold text-slate-800 hover:text-blue-600"
                            >{{ notification.data.message || notification.type }}</Link
                        >
                        <p class="mt-1 flex items-center gap-1 text-xs text-slate-400">
                            <Clock3 class="h-3 w-3" />{{ format(notification.created_at) }}
                        </p>
                    </div>
                    <button
                        v-if="!notification.read_at"
                        class="self-center rounded-lg p-2 text-blue-600 hover:bg-blue-100"
                        title="Mark as read"
                        @click="read(notification)"
                    >
                        <CircleCheck class="h-5 w-5" />
                    </button>
                </article>
            </div>
            <EmptyState
                v-else
                class="mt-6"
                title="You are all caught up"
                description="New assignments, comments, and reminders will appear here."
            /></div
    ></AppLayout>
</template>
