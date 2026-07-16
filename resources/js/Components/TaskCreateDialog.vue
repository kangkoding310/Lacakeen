<script setup>
import Modal from '@/Components/ui/Modal.vue';
import AppSelect from '@/Components/ui/AppSelect.vue';
import InputError from '@/Components/InputError.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({ open: Boolean, defaults: { type: Object, default: () => ({}) } });
const emit = defineEmits(['close']);
const projects = computed(() => usePage().props.taskComposer?.projects ?? []);
const form = useForm({ project_id: '', status_id: '', parent_task_id: '', title: '', description: '', priority: 'medium', start_date: '', due_date: '', assignee_ids: [], label_ids: [] });
const selectedProject = computed(() => projects.value.find((project) => project.id === form.project_id));
const projectOptions = computed(() => projects.value.map((project) => ({ value: project.id, label: project.name })));
const statusOptions = computed(() => (selectedProject.value?.statuses || []).map((status) => ({ value: status.id, label: status.name })));
const priorityOptions = ['urgent', 'high', 'medium', 'low'].map((priority) => ({ value: priority, label: priority[0].toUpperCase() + priority.slice(1) }));

watch(() => props.open, (open) => {
    if (!open) return;
    form.project_id = props.defaults.project_id || projects.value[0]?.id || '';
    form.status_id = props.defaults.status_id || selectedProject.value?.statuses?.[0]?.id || '';
    form.parent_task_id = props.defaults.parent_task_id || '';
    form.due_date = props.defaults.due_date || '';
});
watch(() => form.project_id, () => { form.status_id = selectedProject.value?.statuses?.[0]?.id || ''; form.assignee_ids = []; form.label_ids = []; });

const submit = () => form.post(route('tasks.store'), { preserveScroll: true, onSuccess: () => { form.reset(); emit('close'); } });
</script>

<template>
    <Modal :open="open" title="Create a new task" description="Capture the work, assign owners, and add a due date." @close="$emit('close')">
        <form class="space-y-4" @submit.prevent="submit">
            <div><label class="ui-label">Task title</label><input v-model="form.title" class="ui-input" autofocus placeholder="e.g. Review homepage design" /><InputError class="mt-1" :message="form.errors.title" /></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="ui-label">Project</label><AppSelect v-model="form.project_id" :options="projectOptions" :can-clear="false" /><InputError class="mt-1" :message="form.errors.project_id" /></div>
                <div><label class="ui-label">Status</label><AppSelect v-model="form.status_id" :options="statusOptions" :can-clear="false" /></div>
            </div>
            <div><label class="ui-label">Description</label><textarea v-model="form.description" class="ui-input min-h-24 resize-y py-2.5" placeholder="Add context and acceptance criteria…" /></div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div><label class="ui-label">Priority</label><AppSelect v-model="form.priority" :options="priorityOptions" :searchable="false" :can-clear="false" /></div>
                <div><label class="ui-label">Start date</label><input v-model="form.start_date" type="date" class="ui-input" /></div>
                <div><label class="ui-label">Due date</label><input v-model="form.due_date" type="date" class="ui-input" /><InputError class="mt-1" :message="form.errors.due_date" /></div>
            </div>
            <div><label class="ui-label">Assignees</label><div class="flex flex-wrap gap-2 rounded-xl border border-slate-200 p-2"><label v-for="member in selectedProject?.members || []" :key="member.id" class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-slate-50"><input v-model="form.assignee_ids" type="checkbox" :value="member.id" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" /><img :src="member.avatar" class="h-6 w-6 rounded-full object-cover" />{{ member.name }}</label><span v-if="!selectedProject?.members?.length" class="p-2 text-sm text-slate-400">No project members</span></div></div>
            <div v-if="selectedProject?.labels?.length"><label class="ui-label">Labels</label><div class="flex flex-wrap gap-2"><label v-for="label in selectedProject.labels" :key="label.id" class="cursor-pointer"><input v-model="form.label_ids" type="checkbox" :value="label.id" class="peer sr-only" /><span class="inline-flex rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-500 peer-checked:border-transparent peer-checked:text-white" :style="form.label_ids.includes(label.id) ? { backgroundColor: label.color } : {}">{{ label.name }}</span></label></div></div>
            <footer class="flex justify-end gap-3 pt-2"><button type="button" class="ui-button-secondary" @click="$emit('close')">Cancel</button><button class="ui-button-primary" :disabled="form.processing || !form.project_id">{{ form.processing ? 'Creating…' : 'Create task' }}</button></footer>
        </form>
    </Modal>
</template>
