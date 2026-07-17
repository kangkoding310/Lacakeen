<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import {
    CalendarDays,
    CheckCircle2,
    Github,
    MessageSquare,
    Settings2,
    Video,
} from 'lucide-vue-next';
const props = defineProps({ integrations: Array });
const icons = {
    slack: MessageSquare,
    'google-calendar': CalendarDays,
    github: Github,
    zoom: Video,
};
const colors = {
    slack: 'bg-violet-100 text-violet-700',
    'google-calendar': 'bg-blue-100 text-blue-700',
    github: 'bg-slate-900 text-white',
    zoom: 'bg-sky-100 text-sky-700',
};
const toggle = (integration) =>
    router.patch(
        route('integrations.update', integration.id),
        { connected: integration.status !== 'connected' },
        { preserveScroll: true }
    );
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
                    <button
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
