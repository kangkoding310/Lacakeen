<script setup lang="ts">
import AvatarStack from '@/Components/ui/AvatarStack.vue';
import AppSelect from '@/Components/ui/AppSelect.vue';
import SubtaskList from '@/Components/SubtaskList.vue';
import { formatMediumDate } from '@/utils/date';
import { useTaskComposer } from '@/composables/useTaskComposer';
import { taskService } from '@/services/taskService';
import { TASK_PRIORITY_OPTIONS } from '@/constants/taskPriority';
import type { Task, TaskStatusRef } from '@/types/task';
import { Link, useForm } from '@inertiajs/vue3';
import { Download, Paperclip, Send, Trash2, X } from 'lucide-vue-next';
import { computed, watch } from 'vue';

const props = defineProps<{
    task: Task | null;
    open: boolean;
    statuses?: TaskStatusRef[];
}>();
defineEmits<{ close: [] }>();
const edit = useForm({
    title: '',
    description: '',
    status_id: '',
    priority: '',
    due_date: '',
    assignee_ids: [] as number[],
    label_ids: [] as string[],
});
const comment = useForm({ comment: '' });
const attachment = useForm<{ attachment: File | null }>({ attachment: null });
const { findProject } = useTaskComposer();
const projectData = computed(() => findProject(props.task?.project_id));
const statusOptions = computed(() =>
    (props.statuses ?? []).map((status) => ({ value: status.id, label: status.name }))
);
const priorityOptions = TASK_PRIORITY_OPTIONS;
watch(
    () => props.task,
    (task) => {
        if (task)
            Object.assign(edit, {
                title: task.title,
                description: task.description || '',
                status_id: task.status_id,
                priority: task.priority,
                due_date: task.due_date || '',
                assignee_ids: task.assignees?.map((user) => user.id) || [],
                label_ids: task.labels?.map((label) => label.id) || [],
            });
    },
    { immediate: true }
);
const activity = computed(() => props.task?.activityLogs || []);
const save = () => {
    if (props.task) edit.patch(route('tasks.update', props.task.id), { preserveScroll: true });
};
const addComment = () => {
    if (!props.task) return;
    comment.post(route('tasks.comments.store', props.task.id), {
        preserveScroll: true,
        onSuccess: () => comment.reset(),
    });
};
const upload = () => {
    if (!props.task) return;
    attachment.post(route('tasks.attachments.store', props.task.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => attachment.reset(),
    });
};
const onAttachmentSelected = (event: Event) => {
    attachment.attachment = (event.target as HTMLInputElement).files?.[0] ?? null;
    upload();
};
const remove = () => {
    if (props.task && confirm(`Delete ${props.task.code}?`)) taskService.destroy(props.task.id);
};
const formatTaskDate = (date: string | null) => formatMediumDate(date);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition"
            enter-from-class="opacity-0"
            leave-active-class="transition"
            leave-to-class="opacity-0"
            ><button
                v-if="open"
                class="fixed inset-0 z-[90] bg-slate-950/35 backdrop-blur-sm"
                aria-label="Close task"
                @click="$emit('close')"
        /></Transition>
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-x-full"
            leave-active-class="transition duration-200 ease-in"
            leave-to-class="translate-x-full"
        >
            <aside
                v-if="open && task"
                class="fixed inset-y-0 right-0 z-[95] flex w-full max-w-2xl flex-col bg-white shadow-2xl"
            >
                <header
                    class="flex items-center justify-between border-b border-slate-200 px-5 py-4"
                >
                    <div>
                        <p class="text-xs font-bold text-blue-600">{{ task.code }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">{{ task.project?.name }}</p>
                    </div>
                    <div class="flex gap-2">
                        <button
                            class="ui-icon-button text-red-500"
                            aria-label="Delete task"
                            @click="remove"
                        >
                            <Trash2 class="h-4 w-4" /></button
                        ><button class="ui-icon-button" aria-label="Close" @click="$emit('close')">
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </header>
                <div class="flex-1 overflow-y-auto p-5 sm:p-7">
                    <form class="space-y-5" @submit.prevent="save">
                        <textarea
                            v-model="edit.title"
                            class="w-full resize-none border-0 p-0 text-2xl font-bold text-slate-950 outline-none focus:ring-0"
                        ></textarea>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <AppSelect
                                v-model="edit.status_id"
                                :options="statusOptions"
                                :can-clear="false"
                            />
                            <AppSelect
                                v-model="edit.priority"
                                :options="priorityOptions"
                                :searchable="false"
                                :can-clear="false"
                            /><input v-model="edit.due_date" type="date" class="ui-input" />
                        </div>
                        <div>
                            <label class="ui-label">Description</label
                            ><textarea
                                v-model="edit.description"
                                class="ui-input min-h-32 resize-y py-3"
                                placeholder="Add a description…"
                            />
                        </div>
                        <div>
                            <label class="ui-label">Assignees</label>
                            <div class="flex flex-wrap gap-2">
                                <label
                                    v-for="member in projectData?.members || []"
                                    :key="member.id"
                                    class="cursor-pointer"
                                    ><input
                                        v-model="edit.assignee_ids"
                                        type="checkbox"
                                        :value="member.id"
                                        class="peer sr-only"
                                    /><span
                                        class="flex items-center gap-2 rounded-lg border border-slate-200 px-2 py-1.5 text-xs font-semibold peer-checked:border-blue-300 peer-checked:bg-blue-50 peer-checked:text-blue-700"
                                        ><img
                                            :src="
                                                member.avatar ||
                                                `https://ui-avatars.com/api/?name=${encodeURIComponent(member.name)}`
                                            "
                                            class="h-5 w-5 rounded-full object-cover"
                                        />{{ member.name }}</span
                                    ></label
                                >
                            </div>
                        </div>
                        <div v-if="projectData?.labels?.length">
                            <label class="ui-label">Labels</label>
                            <div class="flex flex-wrap gap-2">
                                <label
                                    v-for="label in projectData.labels"
                                    :key="label.id"
                                    class="cursor-pointer"
                                    ><input
                                        v-model="edit.label_ids"
                                        type="checkbox"
                                        :value="label.id"
                                        class="peer sr-only"
                                    /><span
                                        class="inline-flex rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-500 peer-checked:border-transparent peer-checked:text-white"
                                        :style="
                                            edit.label_ids.includes(label.id)
                                                ? { backgroundColor: label.color }
                                                : {}
                                        "
                                        >{{ label.name }}</span
                                    ></label
                                >
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <AvatarStack :users="task.assignees || []" size="md" /><button
                                class="ui-button-primary"
                                :disabled="edit.processing"
                            >
                                {{ edit.processing ? 'Saving…' : 'Save changes' }}
                            </button>
                        </div>
                    </form>

                    <SubtaskList
                        :key="task.id"
                        class="mt-8 border-t border-slate-100 pt-6"
                        :task="task"
                    />

                    <section class="mt-8 border-t border-slate-100 pt-6">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="font-bold text-slate-900">Attachments</h3>
                            <label class="ui-button-secondary h-9 cursor-pointer">
                                <Paperclip class="h-4 w-4" />Upload<input
                                    type="file"
                                    class="hidden"
                                    @change="onAttachmentSelected"
                                />
                            </label>
                        </div>
                        <div v-if="task.attachments?.length" class="space-y-2">
                            <a
                                v-for="file in task.attachments"
                                :key="file.id"
                                :href="`/storage/${file.file_path}`"
                                target="_blank"
                                class="flex items-center justify-between rounded-xl border border-slate-200 p-3 text-sm hover:bg-slate-50"
                                ><span class="truncate">{{ file.file_name }}</span>
                                <Download class="h-4 w-4 text-slate-400" />
                            </a>
                        </div>
                        <p v-else class="text-sm text-slate-400">No attachments yet.</p>
                    </section>

                    <section class="mt-8 border-t border-slate-100 pt-6">
                        <h3 class="font-bold text-slate-900">
                            Comments
                            <span class="text-slate-400">{{ task.comments?.length || 0 }}</span>
                        </h3>
                        <form class="mt-3 flex gap-2" @submit.prevent="addComment">
                            <input
                                v-model="comment.comment"
                                class="ui-input"
                                placeholder="Write a comment…"
                            /><button
                                class="ui-button-primary w-11 px-0"
                                :disabled="comment.processing"
                            >
                                <Send class="h-4 w-4" />
                            </button>
                        </form>
                        <div class="mt-5 space-y-4">
                            <div
                                v-for="item in task.comments || []"
                                :key="item.id"
                                class="flex gap-3"
                            >
                                <img
                                    :src="
                                        item.user?.avatar ||
                                        `https://ui-avatars.com/api/?name=${encodeURIComponent(item.user?.name)}`
                                    "
                                    class="h-8 w-8 rounded-full object-cover"
                                />
                                <div class="min-w-0 rounded-xl bg-slate-50 px-3 py-2">
                                    <p class="text-xs font-bold text-slate-700">
                                        {{ item.user?.name }}
                                        <span class="ml-2 font-normal text-slate-400">{{
                                            formatTaskDate(item.created_at)
                                        }}</span>
                                    </p>
                                    <p class="mt-1 text-sm text-slate-600">{{ item.comment }}</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="mt-8 border-t border-slate-100 pt-6">
                        <h3 class="font-bold text-slate-900">Activity</h3>
                        <div class="mt-3 space-y-3">
                            <div
                                v-for="item in activity"
                                :key="item.id"
                                class="flex items-center gap-3 text-sm"
                            >
                                <span class="h-2 w-2 rounded-full bg-blue-500" /><span
                                    class="text-slate-500"
                                    ><strong class="text-slate-700">{{
                                        item.user?.name || 'System'
                                    }}</strong>
                                    {{ item.action.replaceAll('_', ' ') }}</span
                                ><span class="ml-auto text-xs text-slate-400">{{
                                    formatTaskDate(item.created_at)
                                }}</span>
                            </div>
                        </div>
                    </section>
                    <Link
                        :href="route('tasks.show', task.id)"
                        class="mt-7 inline-flex text-sm font-semibold text-blue-600"
                        >Open full task page →</Link
                    >
                </div>
            </aside>
        </Transition>
    </Teleport>
</template>
