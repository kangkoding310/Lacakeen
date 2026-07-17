<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AvatarStack from '@/Components/ui/AvatarStack.vue';
import AppSelect from '@/Components/ui/AppSelect.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import TaskCreateDialog from '@/Components/TaskCreateDialog.vue';
import { formatDate } from '@/utils/date';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    CheckSquare,
    Filter,
    MoreHorizontal,
    Plus,
    Search,
    Trash2,
    UserPlus,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({ tasks: Object, projects: Array, members: Array, filters: Object });
const createOpen = ref(false);
const selected = ref([]);
const query = useForm({
    search: props.filters.search || '',
    project: props.filters.project || '',
    status: props.filters.status || '',
    priority: props.filters.priority || '',
    assignee: props.filters.assignee || '',
});
const bulk = useForm({ task_ids: [], action: 'assign', status_id: '', assignee_id: '' });
const statuses = computed(
    () =>
        props.projects.find((project) => project.id === query.project)?.statuses ||
        props.projects[0]?.statuses ||
        []
);
const projectOptions = computed(() =>
    props.projects.map((project) => ({ value: project.id, label: project.name }))
);
const statusOptions = computed(() =>
    statuses.value.map((status) => ({ value: status.id, label: status.name }))
);
const memberOptions = computed(() =>
    props.members.map((member) => ({ value: member.id, label: member.name }))
);
const priorityOptions = ['urgent', 'high', 'medium', 'low'].map((priority) => ({
    value: priority,
    label: priority[0].toUpperCase() + priority.slice(1),
}));
const allSelected = computed(
    () => props.tasks.data.length && selected.value.length === props.tasks.data.length
);
const priorityClass = {
    urgent: 'bg-red-50 text-red-600',
    high: 'bg-orange-50 text-orange-600',
    medium: 'bg-violet-50 text-violet-600',
    low: 'bg-emerald-50 text-emerald-600',
};
const applyFilters = () =>
    router.get(route('tasks.index'), query.data(), { preserveState: true, replace: true });
const toggleAll = () => {
    selected.value = allSelected.value ? [] : props.tasks.data.map((task) => task.id);
};
const applyBulk = () => {
    bulk.task_ids = selected.value;
    bulk.post(route('tasks.bulk'), {
        preserveScroll: true,
        onSuccess: () => {
            selected.value = [];
        },
    });
};
const bulkDelete = () => {
    if (!confirm(`Delete ${selected.value.length} tasks?`)) return;
    bulk.action = 'delete';
    applyBulk();
};
const formatTaskDate = (date) =>
    formatDate(date, { month: 'short', day: '2-digit', year: '2-digit' }, 'No due date');
</script>

<template>
    <Head title="Tasks" />
    <AppLayout title="Tasks">
        <div class="page-shell">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-950">All tasks</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Plan, filter, and update work across every project.
                    </p>
                </div>
                <button class="ui-button-primary" @click="createOpen = true">
                    <Plus class="h-4 w-4" />Create task
                </button>
            </div>

            <form
                class="ui-card mt-6 grid gap-3 p-3 sm:grid-cols-2 xl:grid-cols-[1.5fr_repeat(4,1fr)_auto]"
                @submit.prevent="applyFilters"
            >
                <div class="relative">
                    <Search
                        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    /><input
                        v-model="query.search"
                        class="ui-input pl-9"
                        placeholder="Search code or title"
                    />
                </div>
                <AppSelect
                    v-model="query.project"
                    :options="projectOptions"
                    placeholder="All projects"
                    @change="query.status = ''"
                />
                <AppSelect
                    v-model="query.status"
                    :options="statusOptions"
                    placeholder="All statuses"
                />
                <AppSelect
                    v-model="query.priority"
                    :options="priorityOptions"
                    placeholder="All priorities"
                    :searchable="false"
                />
                <AppSelect
                    v-model="query.assignee"
                    :options="memberOptions"
                    placeholder="All assignees"
                />
                <button class="ui-button-secondary"><Filter class="h-4 w-4" />Filter</button>
            </form>

            <Transition
                enter-active-class="transition"
                enter-from-class="-translate-y-2 opacity-0"
                leave-active-class="transition"
                leave-to-class="-translate-y-2 opacity-0"
                ><div
                    v-if="selected.length"
                    class="mt-4 flex flex-wrap items-center gap-2 rounded-xl bg-slate-900 p-3 text-white"
                >
                    <span class="px-2 text-xs font-semibold">{{ selected.length }} selected</span
                    ><AppSelect
                        v-model="bulk.assignee_id"
                        :options="memberOptions"
                        placeholder="Choose assignee"
                        class="w-48 text-slate-900"
                    /><button
                        class="ui-button h-9 bg-white/10 px-3 text-xs hover:bg-white/20"
                        :disabled="!bulk.assignee_id"
                        @click="
                            bulk.action = 'assign';
                            applyBulk();
                        "
                    >
                        <UserPlus class="h-4 w-4" />Assign</button
                    ><AppSelect
                        v-model="bulk.status_id"
                        :options="statusOptions"
                        placeholder="Choose status"
                        class="w-44 text-slate-900"
                    /><button
                        class="ui-button h-9 bg-white/10 px-3 text-xs hover:bg-white/20"
                        :disabled="!bulk.status_id"
                        @click="
                            bulk.action = 'status';
                            applyBulk();
                        "
                    >
                        <CheckSquare class="h-4 w-4" />Change status</button
                    ><button
                        class="ml-auto ui-button h-9 bg-red-500/20 px-3 text-xs text-red-200 hover:bg-red-500/30"
                        @click="bulkDelete"
                    >
                        <Trash2 class="h-4 w-4" />Delete
                    </button>
                </div></Transition
            >

            <div class="table-shell mt-4 overflow-x-auto">
                <table class="w-full min-w-[980px] text-left">
                    <thead
                        class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-400"
                    >
                        <tr>
                            <th class="w-12 px-4 py-3">
                                <input
                                    type="checkbox"
                                    class="rounded border-slate-300 text-blue-600"
                                    :checked="allSelected"
                                    @change="toggleAll"
                                />
                            </th>
                            <th class="px-3 py-3">Task</th>
                            <th class="px-3 py-3">Project</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3">Priority</th>
                            <th class="px-3 py-3">Assignee</th>
                            <th class="px-3 py-3">Due date</th>
                            <th class="w-14 px-3 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="task in tasks.data"
                            :key="task.id"
                            class="text-sm transition hover:bg-slate-50/80"
                        >
                            <td class="px-4 py-4">
                                <input
                                    v-model="selected"
                                    type="checkbox"
                                    :value="task.id"
                                    class="rounded border-slate-300 text-blue-600"
                                />
                            </td>
                            <td class="px-3 py-4">
                                <Link :href="route('tasks.show', task.id)" class="block"
                                    ><span class="text-[11px] font-bold text-blue-600">{{
                                        task.code
                                    }}</span>
                                    <p
                                        class="mt-0.5 font-semibold text-slate-800 hover:text-blue-700"
                                    >
                                        {{ task.title }}
                                    </p></Link
                                >
                            </td>
                            <td class="px-3 py-4">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="h-2 w-2 rounded-full"
                                        :style="{ backgroundColor: task.project.color }"
                                    /><span class="text-slate-600">{{ task.project.name }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-4">
                                <span
                                    class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600"
                                    >{{ task.status.name }}</span
                                >
                            </td>
                            <td class="px-3 py-4">
                                <span
                                    class="rounded-lg px-2.5 py-1 text-xs font-bold capitalize"
                                    :class="priorityClass[task.priority]"
                                    >{{ task.priority }}</span
                                >
                            </td>
                            <td class="px-3 py-4"><AvatarStack :users="task.assignees" /></td>
                            <td
                                class="px-3 py-4 text-xs font-medium"
                                :class="
                                    task.due_date && new Date(task.due_date) < new Date()
                                        ? 'text-red-500'
                                        : 'text-slate-500'
                                "
                            >
                                {{ formatTaskDate(task.due_date) }}
                            </td>
                            <td class="px-3 py-4">
                                <Link
                                    :href="route('tasks.show', task.id)"
                                    class="ui-icon-button h-8 w-8 border-0"
                                    ><MoreHorizontal class="h-4 w-4"
                                /></Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="!tasks.data.length" class="p-5">
                    <EmptyState
                        title="No matching tasks"
                        description="Try changing the filters or create a new task."
                    />
                </div>
            </div>

            <div v-if="tasks.links?.length > 3" class="mt-4 flex justify-end gap-1">
                <Link
                    v-for="link in tasks.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    v-html="link.label"
                    class="grid min-h-9 min-w-9 place-items-center rounded-lg px-3 text-xs font-semibold"
                    :class="[
                        link.active
                            ? 'bg-blue-600 text-white'
                            : 'border border-slate-200 bg-white text-slate-500',
                        !link.url && 'pointer-events-none opacity-40',
                    ]"
                />
            </div>
        </div>
        <TaskCreateDialog :open="createOpen" @close="createOpen = false" />
    </AppLayout>
</template>
