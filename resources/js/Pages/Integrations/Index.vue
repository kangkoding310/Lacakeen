<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Integration } from '@/types/integration';
import { Head, router } from '@inertiajs/vue3';
import {
    CalendarDays,
    CheckCircle2,
    Github,
    MessageSquare,
    Settings2,
    Video,
} from 'lucide-vue-next';

defineProps<{ integrations: Integration[] }>();
const icons: Record<string, typeof MessageSquare> = {
    slack: MessageSquare,
    'google-calendar': CalendarDays,
    github: Github,
    zoom: Video,
};
const colors: Record<string, string> = {
    slack: 'bg-violet-100 text-violet-700',
    'google-calendar': 'bg-blue-100 text-blue-700',
    github: 'bg-slate-900 text-white',
    zoom: 'bg-sky-100 text-sky-700',
};
const toggle = (integration: Integration) =>
    router.patch(
        route('integrations.update', integration.id),
        { connected: integration.status !== 'connected' },
        { preserveScroll: true }
    );
const disconnectGoogleCalendar = () =>
    router.delete(route('integrations.google-calendar.disconnect'), { preserveScroll: true });
</script>
<template>
    <Head title="Integrations" />
    <AppLayout title="Integrations" section="Tools">
        <div class="page-shell">
            <div>
                <h1 class="text-2xl font-bold">Integrations</h1>
                <p class="mt-1 text-sm text-slate-500">Connect the tools your team already uses.</p>
            </div>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="integration in integrations"
                    :key="integration.id"
                    class="ui-card p-5"
                >
                    <div class="flex items-start justify-between">
                        <div
                            class="grid h-12 w-12 place-items-center rounded-2xl"
                            :class="colors[integration.type]"
                        >
                            <component :is="icons[integration.type] || Settings2" class="h-6 w-6" />
                        </div>
                        <span
                            v-if="integration.status === 'connected'"
                            class="flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-bold text-emerald-600"
                        >
                            <CheckCircle2 class="h-3 w-3" />Connected
                        </span>
                    </div>
                    <h2 class="mt-5 font-bold">{{ integration.name }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Sync updates and keep your team workflow connected without leaving Lacakeen.
                    </p>
                    <p
                        v-if="
                            integration.type === 'google-calendar' &&
                            integration.google_account_email
                        "
                        class="mt-1 text-xs text-slate-400"
                    >
                        Connected as {{ integration.google_account_email }}
                    </p>
                    <a
                        v-if="
                            integration.type === 'google-calendar' &&
                            integration.status !== 'connected'
                        "
                        :href="route('integrations.google-calendar.connect')"
                        class="ui-button-primary mt-5 w-full"
                    >
                        Connect
                    </a>
                    <button
                        v-else-if="integration.type === 'google-calendar'"
                        class="ui-button-secondary mt-5 w-full text-red-600"
                        @click="disconnectGoogleCalendar"
                    >
                        Disconnect
                    </button>
                    <button
                        v-else
                        class="mt-5 w-full"
                        :class="
                            integration.status === 'connected'
                                ? 'ui-button-secondary text-red-600'
                                : 'ui-button-primary'
                        "
                        @click="toggle(integration)"
                    >
                        {{ integration.status === 'connected' ? 'Disconnect' : 'Connect' }}
                    </button>
                </article>
            </div>
        </div>
    </AppLayout>
</template>
