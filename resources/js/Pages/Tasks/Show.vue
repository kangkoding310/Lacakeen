<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AvatarStack from '@/Components/ui/AvatarStack.vue';
import SubtaskList from '@/Components/SubtaskList.vue';
import { formatDate } from '@/lib/date';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Download, Paperclip, Send } from 'lucide-vue-next';

const props = defineProps({ task: Object });
const comment = useForm({ comment: '' });
const addComment = () => comment.post(route('tasks.comments.store', props.task.id), { preserveScroll: true, onSuccess: () => comment.reset() });
const formatTaskDate = (date) => formatDate(date, { month: 'long', day: 'numeric', year: 'numeric' }, 'Not set');
</script>

<template>
    <Head :title="task.code" />
    <AppLayout :title="task.code" section="Tasks">
        <div class="page-shell max-w-6xl">
            <Link :href="route('tasks.index')" class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-blue-600"><ArrowLeft class="h-4 w-4" />Back to tasks</Link>
            <div class="grid gap-5 lg:grid-cols-[1fr_320px]">
                <main class="ui-card p-6 sm:p-8">
                    <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-bold text-blue-600">{{ task.code }} · {{ task.project.name }}</p><h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ task.title }}</h1></div><span class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold capitalize">{{ task.priority }}</span></div>
                    <div class="prose mt-8 max-w-none text-sm leading-7 text-slate-600">{{ task.description || 'No description has been added yet.' }}</div>
                    <SubtaskList v-if="!task.parent_task_id" class="mt-8 border-t border-slate-100 pt-6" :task="task" />
                    <section class="mt-8 border-t border-slate-100 pt-6"><h2 class="font-bold">Comments ({{ task.comments.length }})</h2><form class="mt-4 flex gap-2" @submit.prevent="addComment"><input v-model="comment.comment" class="ui-input" placeholder="Share an update…" /><button class="ui-button-primary w-11 px-0"><Send class="h-4 w-4" /></button></form><div class="mt-6 space-y-4"><div v-for="item in task.comments" :key="item.id" class="flex gap-3"><img :src="item.user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name)}`" class="h-9 w-9 rounded-full object-cover" /><div class="rounded-xl bg-slate-50 px-4 py-3"><p class="text-xs font-bold">{{ item.user.name }} <span class="ml-2 font-normal text-slate-400">{{ formatTaskDate(item.created_at) }}</span></p><p class="mt-1 text-sm text-slate-600">{{ item.comment }}</p></div></div></div></section>
                </main>
                <aside class="space-y-4"><div class="ui-card p-5"><h2 class="text-sm font-bold">Details</h2><dl class="mt-5 space-y-4 text-sm"><div class="flex justify-between gap-4"><dt class="text-slate-400">Status</dt><dd class="font-semibold">{{ task.status.name }}</dd></div><div class="flex justify-between gap-4"><dt class="text-slate-400">Due date</dt><dd class="font-semibold">{{ formatTaskDate(task.due_date) }}</dd></div><div><dt class="mb-2 text-slate-400">Assignees</dt><dd><AvatarStack :users="task.assignees" size="md" /></dd></div><div><dt class="mb-2 text-slate-400">Labels</dt><dd class="flex flex-wrap gap-1"><span v-for="label in task.labels" :key="label.id" class="rounded-lg px-2 py-1 text-xs font-semibold text-white" :style="{ backgroundColor: label.color }">{{ label.name }}</span></dd></div></dl></div><div class="ui-card p-5"><h2 class="flex items-center gap-2 text-sm font-bold"><Paperclip class="h-4 w-4" />Attachments</h2><div class="mt-4 space-y-2"><a v-for="file in task.attachments" :key="file.id" :href="`/storage/${file.file_path}`" class="flex items-center justify-between rounded-lg bg-slate-50 p-3 text-xs font-semibold"><span class="truncate">{{ file.file_name }}</span><Download class="h-4 w-4" /></a><p v-if="!task.attachments.length" class="text-xs text-slate-400">No files uploaded.</p></div></div></aside>
            </div>
        </div>
    </AppLayout>
</template>
