<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import { Head, Link } from '@inertiajs/vue3';
import { AtSign, CheckCircle2, Inbox, UserCheck } from 'lucide-vue-next';
import { computed, ref } from 'vue';
const props = defineProps({ items: Object });
const filter = ref('all');
const filtered = computed(() => props.items.data.filter((item) => filter.value === 'all' || (filter.value === 'unread' && !item.read_at) || (filter.value === 'mentions' && item.data.message?.includes('@')) || (filter.value === 'assigned' && item.data.message?.includes('assigned'))));
</script>
<template>
    <Head title="Inbox" />
    <AppLayout title="Inbox" section="Tools"><div class="page-shell max-w-6xl"><div><h1 class="text-2xl font-bold">Inbox</h1><p class="mt-1 text-sm text-slate-500">Mentions, assignments, and requests in one actionable list.</p></div><div class="mt-6 grid gap-4 lg:grid-cols-[220px_1fr]"><aside class="ui-card h-fit p-2"><button v-for="item in [{id:'all',label:'Everything',icon:Inbox},{id:'unread',label:'Unread',icon:CheckCircle2},{id:'mentions',label:'Mentions',icon:AtSign},{id:'assigned',label:'Assigned to me',icon:UserCheck}]" :key="item.id" class="flex h-10 w-full items-center gap-3 rounded-xl px-3 text-sm font-semibold" :class="filter === item.id ? 'bg-blue-50 text-blue-700' : 'text-slate-500 hover:bg-slate-50'" @click="filter = item.id"><component :is="item.icon" class="h-4 w-4" />{{ item.label }}</button></aside><div v-if="filtered.length" class="ui-card divide-y divide-slate-100 overflow-hidden"><Link v-for="item in filtered" :key="item.id" :href="item.data.url || '#'" class="flex items-start gap-4 p-5 hover:bg-slate-50"><span class="mt-2 h-2 w-2 shrink-0 rounded-full" :class="item.read_at ? 'bg-slate-200' : 'bg-blue-600'" /><div><p class="text-sm font-semibold text-slate-800">{{ item.data.message }}</p><p class="mt-1 text-xs text-slate-400">{{ new Date(item.created_at).toLocaleString() }}</p></div></Link></div><EmptyState v-else title="Inbox zero" description="No items match this filter." /></div></div></AppLayout>
</template>
