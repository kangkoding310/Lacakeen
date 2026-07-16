<script setup>
import AppSelect from '@/Components/ui/AppSelect.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { CornerDownLeft, ListTree, Plus, X } from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';

const props = defineProps({ task: Object });
const adding = ref(false);
const input = ref(null);
const form = useForm({
    project_id: props.task.project_id, status_id: props.task.status_id, parent_task_id: props.task.id,
    title: '', priority: 'medium', assignee_ids: [],
});
const project = computed(() => usePage().props.taskComposer?.projects?.find((item) => item.id === props.task.project_id));
const statusOptions = computed(() => (project.value?.statuses || []).map((status) => ({ value: status.id, label: status.name })));
const memberOptions = computed(() => (project.value?.members || []).map((member) => ({ value: member.id, label: member.name, avatar: member.avatar })));
const priorityOptions = ['urgent', 'high', 'medium', 'low'].map((priority) => ({ value: priority, label: priority[0].toUpperCase() + priority.slice(1) }));
const completed = computed(() => props.task.subtasks?.filter((item) => item.status?.name?.toLowerCase() === 'completed').length || 0);
const progress = computed(() => props.task.subtasks?.length ? Math.round(completed.value / props.task.subtasks.length * 100) : 0);
const start = () => { adding.value = true; nextTick(() => input.value?.focus()); };
const cancel = () => { adding.value = false; form.reset('title'); form.clearErrors(); };
const submit = () => {
    if (!form.title.trim()) return;
    form.post(route('tasks.store'), { preserveScroll: true, onSuccess: cancel });
};
const update = (subtask, field, value) => router.patch(route('tasks.update', subtask.id), field === 'assignee_ids' ? { assignee_ids: value ? [value] : [] } : { [field]: value }, { preserveScroll: true });
</script>

<template>
    <section>
        <div class="flex items-center justify-between"><h3 class="flex items-center gap-2 font-bold text-slate-900"><ListTree class="h-4 w-4 text-slate-400" />Subtasks <span class="text-slate-400">{{ task.subtasks?.length || 0 }}</span></h3><button v-if="!adding" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-blue-600" title="Add subtask" aria-label="Add subtask" @click="start"><Plus class="h-5 w-5" /></button></div>
        <div v-if="task.subtasks?.length" class="mt-3"><div class="mb-3 flex items-center gap-3"><div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-200"><div class="h-full rounded-full bg-blue-600 transition-all" :style="{ width: `${progress}%` }" /></div><span class="text-xs font-semibold text-slate-500">{{ progress }}% Done</span></div><div class="overflow-x-auto rounded-xl border border-slate-200"><div class="min-w-[720px]"><div class="grid grid-cols-[1fr_120px_180px_150px] gap-2 border-b border-slate-200 bg-slate-50 px-3 py-2 text-[10px] font-bold uppercase tracking-wide text-slate-400"><span>Work</span><span>Priority</span><span>Assignee</span><span>Status</span></div><div v-for="subtask in task.subtasks" :key="subtask.id" class="grid grid-cols-[1fr_120px_180px_150px] items-center gap-2 border-b border-slate-100 px-3 py-2 last:border-0"><div class="min-w-0"><p class="text-[10px] font-bold text-blue-600">{{ subtask.code }}</p><p class="truncate text-sm font-medium text-slate-800">{{ subtask.title }}</p></div><AppSelect :model-value="subtask.priority" :options="priorityOptions" :searchable="false" :can-clear="false" @update:model-value="update(subtask, 'priority', $event)" /><AppSelect :model-value="subtask.assignees?.[0]?.id || null" :options="memberOptions" placeholder="Unassigned" @update:model-value="update(subtask, 'assignee_ids', $event)"><template #option="{ option }"><div class="flex items-center gap-2"><img :src="option.avatar" class="h-6 w-6 rounded-full object-cover" /><span>{{ option.label }}</span></div></template><template #singlelabel="{ value }"><div class="flex items-center gap-2"><img :src="value.avatar" class="h-6 w-6 rounded-full object-cover" /><span class="truncate">{{ value.label }}</span></div></template></AppSelect><AppSelect :model-value="subtask.status_id" :options="statusOptions" :searchable="false" :can-clear="false" @update:model-value="update(subtask, 'status_id', $event)" /></div></div></div>
        </div>
        <form v-if="adding" class="mt-3" @submit.prevent="submit"><div class="flex rounded-xl border-2 border-blue-500 bg-white p-1 shadow-sm ring-2 ring-blue-100"><input ref="input" v-model="form.title" class="min-w-0 flex-1 border-0 px-2 text-sm outline-none focus:ring-0" placeholder="Name this subtask" maxlength="255" @keydown.esc.prevent="cancel" /><button class="grid h-8 w-9 place-items-center rounded-lg bg-blue-600 text-white disabled:opacity-40" :disabled="form.processing || !form.title.trim()" aria-label="Save subtask"><CornerDownLeft class="h-4 w-4" /></button></div><div class="mt-2 flex items-center justify-between"><p class="text-xs text-red-600">{{ form.errors.title }}</p><button type="button" class="flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-slate-900" @click="cancel"><X class="h-3.5 w-3.5" />Cancel</button></div></form>
        <button v-else-if="!task.subtasks?.length" class="mt-3 flex w-full items-center gap-2 rounded-xl border border-dashed border-slate-300 px-4 py-3 text-left text-sm text-slate-500 hover:border-blue-300 hover:text-blue-600" @click="start"><Plus class="h-4 w-4" />Add the first subtask</button>
    </section>
</template>
