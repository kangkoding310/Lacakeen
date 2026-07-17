<script setup lang="ts">
import Modal from '@/Components/ui/Modal.vue';
import AppSelect from '@/Components/ui/AppSelect.vue';
import InputError from '@/Components/InputError.vue';
import RichTextEditor from '@/Components/ui/RichTextEditor.vue';
import { useTaskComposer } from '@/composables/useTaskComposer';
import { TASK_PRIORITY_OPTIONS } from '@/constants/taskPriority';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface TaskCreateDefaults {
    project_id?: string;
    status_id?: string;
    parent_task_id?: string;
    due_date?: string;
}

const props = defineProps<{
    open: boolean;
    defaults?: TaskCreateDefaults;
}>();
const emit = defineEmits<{ close: [] }>();
const { projects } = useTaskComposer();
const form = useForm({
    project_id: '',
    status_id: '',
    parent_task_id: '',
    title: '',
    description: '',
    priority: 'medium',
    start_date: '',
    due_date: '',
    assignee_ids: [] as number[],
    label_ids: [] as string[],
});
const selectedProject = computed(() =>
    projects.value.find((project) => project.id === form.project_id)
);
const projectOptions = computed(() =>
    projects.value.map((project) => ({ value: project.id, label: project.name }))
);
const statusOptions = computed(() =>
    (selectedProject.value?.statuses || []).map((status) => ({
        value: status.id,
        label: status.name,
    }))
);
const priorityOptions = TASK_PRIORITY_OPTIONS;
const dateRange = computed({
    get: (): [string, string] | null =>
        form.start_date && form.due_date ? [form.start_date, form.due_date] : null,
    set: (value: [string, string] | null) => {
        form.start_date = value?.[0] ?? '';
        form.due_date = value?.[1] ?? '';
    },
});

watch(
    () => props.open,
    (open) => {
        if (!open) return;
        form.project_id = props.defaults?.project_id || projects.value[0]?.id || '';
        form.status_id =
            props.defaults?.status_id || selectedProject.value?.statuses?.[0]?.id || '';
        form.parent_task_id = props.defaults?.parent_task_id || '';
        form.due_date = props.defaults?.due_date || '';
    }
);
watch(
    () => form.project_id,
    () => {
        form.status_id = selectedProject.value?.statuses?.[0]?.id || '';
        form.assignee_ids = [];
        form.label_ids = [];
    }
);

const submit = () =>
    form.post(route('tasks.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    });
</script>

<template>
    <Modal
        :open="open"
        title="Create a new task"
        description="Capture the work, assign owners, and add a due date."
        @close="$emit('close')"
    >
        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label class="ui-label">Task title</label
                ><input
                    v-model="form.title"
                    class="ui-input"
                    autofocus
                    placeholder="e.g. Review homepage design"
                />
                <InputError class="mt-1" :message="form.errors.title" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="ui-label">Project</label>
                    <AppSelect
                        v-model="form.project_id"
                        :options="projectOptions"
                        :can-clear="false"
                    />
                    <InputError class="mt-1" :message="form.errors.project_id" />
                </div>
                <div>
                    <label class="ui-label">Status</label>
                    <AppSelect
                        v-model="form.status_id"
                        :options="statusOptions"
                        :can-clear="false"
                    />
                </div>
            </div>
            <div>
                <label class="ui-label">Description</label>
                <RichTextEditor
                    v-model="form.description"
                    placeholder="Add context and acceptance criteria… use @ to mention someone"
                    :mention-users="selectedProject?.members || []"
                />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="ui-label">Priority</label>
                    <AppSelect
                        v-model="form.priority"
                        :options="priorityOptions"
                        :searchable="false"
                        :can-clear="false"
                    />
                </div>
                <div>
                    <label class="ui-label">Start &amp; due date</label>
                    <VueDatePicker
                        v-model="dateRange"
                        range
                        model-type="yyyy-MM-dd"
                        format="dd MMM yyyy"
                        input-class-name="ui-input"
                        placeholder="Select start &amp; due date"
                    />
                    <InputError class="mt-1" :message="form.errors.due_date" />
                </div>
            </div>
            <div>
                <label class="ui-label">Assignees</label>
                <div class="flex flex-wrap gap-2 rounded-xl border border-slate-200 p-2">
                    <label
                        v-for="member in selectedProject?.members || []"
                        :key="member.id"
                        class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-slate-50"
                        ><input
                            v-model="form.assignee_ids"
                            type="checkbox"
                            :value="member.id"
                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        /><img
                            :src="
                                member.avatar ||
                                `https://ui-avatars.com/api/?name=${encodeURIComponent(member.name)}`
                            "
                            class="h-6 w-6 rounded-full object-cover"
                        />{{ member.name }}</label
                    ><span
                        v-if="!selectedProject?.members?.length"
                        class="p-2 text-sm text-slate-400"
                        >No project members</span
                    >
                </div>
            </div>
            <div v-if="selectedProject?.labels?.length">
                <label class="ui-label">Labels</label>
                <div class="flex flex-wrap gap-2">
                    <label
                        v-for="label in selectedProject.labels"
                        :key="label.id"
                        class="cursor-pointer"
                        ><input
                            v-model="form.label_ids"
                            type="checkbox"
                            :value="label.id"
                            class="peer sr-only"
                        /><span
                            class="inline-flex rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-500 peer-checked:border-transparent peer-checked:text-white"
                            :style="
                                form.label_ids.includes(label.id)
                                    ? { backgroundColor: label.color }
                                    : {}
                            "
                            >{{ label.name }}</span
                        ></label
                    >
                </div>
            </div>
            <footer class="flex justify-end gap-3 pt-2">
                <button type="button" class="ui-button-secondary" @click="$emit('close')">
                    Cancel</button
                ><button class="ui-button-primary" :disabled="form.processing || !form.project_id">
                    {{ form.processing ? 'Creating…' : 'Create task' }}
                </button>
            </footer>
        </form>
    </Modal>
</template>
