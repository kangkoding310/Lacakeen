<script setup>
import { Inbox } from 'lucide-vue-next';
defineProps({ title: { type: String, default: 'Nothing here yet' }, description: { type: String, default: 'Create your first item to get started.' } });
</script>
<template><div class="grid min-h-52 place-items-center rounded-2xl border border-dashed border-slate-300 bg-slate-50/70 p-8 text-center"><div><div class="mx-auto mb-3 grid h-11 w-11 place-items-center rounded-xl bg-white text-slate-400 shadow-sm"><Inbox class="h-5 w-5" /></div><h3 class="font-semibold text-slate-900">{{ title }}</h3><p class="mt-1 text-sm text-slate-500">{{ description }}</p><div class="mt-4"><slot /></div></div></div></template>
