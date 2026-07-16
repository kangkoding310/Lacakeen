<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { BookOpen, LifeBuoy, Mail, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
const search = ref('');
const faqs = [
    ['How do I create and assign a task?', 'Use the Create task button in the top bar, select a project and status, then choose one or more assignees.'],
    ['Can I customize Kanban columns?', 'Yes. Add, rename, or remove empty columns directly from the project board.'],
    ['How do notifications work?', 'Assignments, comments, workflow actions, and due-date reminders are delivered to your notification inbox.'],
    ['Can I connect GitHub or Slack?', 'Open Integrations, choose a service, and use Connect. OAuth credentials can be added during deployment.'],
    ['How do project roles differ?', 'Owners and editors can update project work. Viewers can browse it, while workspace admins manage members and roles.'],
];
const filtered = computed(() => faqs.filter(([question, answer]) => `${question} ${answer}`.toLowerCase().includes(search.value.toLowerCase())));
</script>
<template>
    <Head title="Help Center" />
    <AppLayout title="Help Center" section="Support"><div class="page-shell max-w-5xl"><section class="rounded-3xl bg-gradient-to-br from-blue-600 to-violet-700 px-6 py-10 text-center text-white sm:px-12"><LifeBuoy class="mx-auto h-9 w-9" /><h1 class="mt-4 text-3xl font-bold">How can we help?</h1><p class="mt-2 text-sm text-blue-100">Search answers or get in touch with the Lacakeen team.</p><div class="relative mx-auto mt-6 max-w-xl"><Search class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" /><input v-model="search" class="h-12 w-full rounded-2xl border-0 pl-12 pr-4 text-sm text-slate-900 shadow-xl outline-none" placeholder="Search help articles…" /></div></section><section class="mt-6"><h2 class="mb-3 flex items-center gap-2 font-bold"><BookOpen class="h-5 w-5 text-blue-600" />Frequently asked questions</h2><div class="ui-card divide-y divide-slate-100 overflow-hidden"><details v-for="([question, answer], index) in filtered" :key="question" class="group p-5" :open="index === 0"><summary class="cursor-pointer list-none pr-8 text-sm font-bold text-slate-800">{{ question }}</summary><p class="mt-3 max-w-3xl text-sm leading-6 text-slate-500">{{ answer }}</p></details><p v-if="!filtered.length" class="p-8 text-center text-sm text-slate-400">No matching answers found.</p></div></section><section class="ui-card mt-6 flex flex-col items-start justify-between gap-4 p-6 sm:flex-row sm:items-center"><div><h2 class="font-bold">Still need help?</h2><p class="mt-1 text-sm text-slate-500">Our support team usually responds within one business day.</p></div><a href="mailto:support@lacakeen.app?subject=Lacakeen%20support" class="ui-button-primary"><Mail class="h-4 w-4" />Contact support</a></section></div></AppLayout>
</template>
