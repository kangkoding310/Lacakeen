<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import AvatarStack from '@/Components/ui/AvatarStack.vue';
import AppSelect from '@/Components/ui/AppSelect.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import KanbanBoard from '@/Components/KanbanBoard.vue';
import TaskCreateDialog from '@/Components/TaskCreateDialog.vue';
import TaskDetailDrawer from '@/Components/TaskDetailDrawer.vue';
import { promptDialog } from '@/composables/useDialog';
import { usePermissions } from '@/composables/usePermissions';
import { taskStatusService } from '@/services/taskStatusService';
import { TASK_PRIORITIES } from '@/constants/taskPriority';
import { formatDate } from '@/utils/date';
import { useDateStore } from '@/stores/date';
import type { ProjectStatusColumn } from '@/types/project';
import type { Task, TaskAssignee } from '@/types/task';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowDownUp,
    ArrowRight,
    Bolt,
    CalendarDays,
    ChartNoAxesColumn,
    CheckCircle2,
    Download,
    Ellipsis,
    FolderKanban,
    LayoutGrid,
    Plus,
    RefreshCw,
    Search,
    Sparkles,
    Table2,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

interface DashboardProject {
    id: string;
    name: string;
}

const props = defineProps<{
    stats: { activeEmployees: number; activeProjects: number; tasks: number; accomplished: number };
    projects: DashboardProject[];
    selectedProject: DashboardProject | null;
    statuses: ProjectStatusColumn[];
    members: TaskAssignee[];
}>();
const { currentUser } = usePermissions();
const dateStore = useDateStore();
const activeView = ref('Kanban');
const selectedTask = ref<Task | null>(null);
const detailOpen = ref(false);
const createOpen = ref(false);
const createDefaults = ref<{ project_id?: string; status_id?: string }>({});
const allTasks = computed(() => props.statuses.flatMap((status) => status.tasks || []));
const projectOptions = computed(() =>
    props.projects.map((project) => ({ value: project.id, label: project.name }))
);
const views = [
    { name: 'Kanban', icon: LayoutGrid },
    { name: 'Timeline', icon: ChartNoAxesColumn },
    { name: 'Spreadsheet', icon: Table2 },
    { name: 'Calendar', icon: CalendarDays },
];
const cards = computed(() => [
    {
        label: 'Active employees',
        value: props.stats.activeEmployees,
        icon: Users,
        href: route('members'),
    },
    {
        label: 'Active projects',
        value: props.stats.activeProjects,
        icon: FolderKanban,
        href: route('projects.index'),
    },
    {
        label: 'Number of tasks',
        value: props.stats.tasks,
        icon: Table2,
        href: route('tasks.index'),
    },
    {
        label: 'Task accomplished',
        value: `${props.stats.accomplished}%`,
        icon: CheckCircle2,
        href: route('reporting'),
    },
]);
const openTask = (task: Task) => {
    selectedTask.value = task;
    detailOpen.value = true;
};
const addTask = (statusId?: string) => {
    createDefaults.value = { project_id: props.selectedProject?.id, status_id: statusId };
    createOpen.value = true;
};
const addColumn = async () => {
    const name = await promptDialog({
        title: 'New column',
        placeholder: 'Column name',
        validate: (value) => (!value.trim() ? 'Name is required.' : undefined),
    });
    if (name && props.selectedProject)
        taskStatusService.store(
            props.selectedProject.id,
            { name: name.trim() },
            { preserveScroll: true }
        );
};
const switchProject = (project: string) =>
    router.get(route('dashboard'), { project }, { preserveState: false, preserveScroll: true });
const formatTaskDate = (date: string | null) =>
    formatDate(date, { month: 'short', day: '2-digit', year: '2-digit' });

// --- Toolbar: search ---
const boardSearch = ref('');
const boardSearchOpen = ref(false);
const boardSearchInput = ref<HTMLInputElement | null>(null);
const openBoardSearch = () => {
    boardSearchOpen.value = true;
    nextTick(() => boardSearchInput.value?.focus());
};
const closeBoardSearch = () => {
    boardSearchOpen.value = false;
    boardSearch.value = '';
};
const searchedTasks = computed(() => {
    const query = boardSearch.value.trim().toLowerCase();
    if (!query) return allTasks.value;
    return allTasks.value.filter(
        (task) =>
            task.title.toLowerCase().includes(query) || task.code.toLowerCase().includes(query)
    );
});

// --- Toolbar: sort (applies to Spreadsheet/Timeline/Calendar; Kanban keeps its manual drag order) ---
const sortBy = ref<'due_date' | 'priority' | 'title'>('due_date');
const sortDir = ref<'asc' | 'desc'>('asc');
const sortOptions: { value: typeof sortBy.value; label: string }[] = [
    { value: 'due_date', label: 'Due date' },
    { value: 'priority', label: 'Priority' },
    { value: 'title', label: 'Title' },
];
const setSort = (value: typeof sortBy.value) => {
    if (sortBy.value === value) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = value;
        sortDir.value = 'asc';
    }
    closeToolbarMenus();
};
const sortedTasks = computed(() => {
    const direction = sortDir.value === 'asc' ? 1 : -1;
    return [...searchedTasks.value].sort((a, b) => {
        if (sortBy.value === 'priority')
            return (
                (TASK_PRIORITIES.indexOf(a.priority) - TASK_PRIORITIES.indexOf(b.priority)) *
                direction
            );
        if (sortBy.value === 'title') return a.title.localeCompare(b.title) * direction;
        const aTime = a.due_date ? new Date(a.due_date).getTime() : Infinity;
        const bTime = b.due_date ? new Date(b.due_date).getTime() : Infinity;
        return (aTime - bTime) * direction;
    });
});

// --- Toolbar: focus mode (hides the summary sections above the board) ---
const focusMode = ref(false);

// --- Toolbar: quick stats popover ---
const statusStats = computed(() =>
    props.statuses.map((status) => ({
        name: status.name,
        color: status.color,
        count: status.tasks?.length ?? 0,
    }))
);

// --- Toolbar: dropdown menus (native <details>, closed on outside click/Escape/selection) ---
const toolbarRef = ref<HTMLElement | null>(null);
const closeToolbarMenus = () => {
    toolbarRef.value?.querySelectorAll('details[open]').forEach((el) => el.removeAttribute('open'));
};
const handleToolbarOutsideEvent = (event: Event) => {
    if (event instanceof KeyboardEvent) {
        if (event.key === 'Escape') closeToolbarMenus();
        return;
    }
    if (!toolbarRef.value?.contains(event.target as Node)) closeToolbarMenus();
};
onMounted(() => {
    document.addEventListener('pointerdown', handleToolbarOutsideEvent);
    document.addEventListener('keydown', handleToolbarOutsideEvent);
});
onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', handleToolbarOutsideEvent);
    document.removeEventListener('keydown', handleToolbarOutsideEvent);
});

// --- Toolbar: export currently visible tasks (respects search + sort) as CSV ---
const exportCsv = () => {
    closeToolbarMenus();
    const escape = (value: string) => `"${value.replace(/"/g, '""')}"`;
    const rows = [
        ['Code', 'Title', 'Status', 'Priority', 'Due date'],
        ...sortedTasks.value.map((task) => [
            task.code,
            task.title,
            task.status?.name ?? '',
            task.priority,
            task.due_date ?? '',
        ]),
    ];
    const csv = rows.map((row) => row.map((cell) => escape(String(cell))).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `${props.selectedProject?.name ?? 'tasks'}-${new Date().toISOString().slice(0, 10)}.csv`;
    link.click();
    URL.revokeObjectURL(url);
};

// --- Toolbar: manual resync without a full navigation ---
const refreshBoard = () => {
    closeToolbarMenus();
    router.reload({ only: ['statuses', 'stats'] });
};
</script>

<template>
    <Head title="Overview" />
    <AppLayout title="Overview">
        <div class="page-shell">
            <template v-if="!focusMode">
                <section class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                    <div>
                        <p class="mb-2 text-xs font-bold uppercase tracking-[.18em] text-blue-600">
                            {{ dateStore.dayName }} focus
                        </p>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                            Welcome back, {{ currentUser.name.split(' ')[0] }}!
                        </h1>
                        <p class="mt-2 text-sm text-slate-500">
                            Stay on top of your tasks, monitor progress, and track status.
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <AvatarStack :users="members" :max="4" size="md" />
                        <div class="w-52">
                            <AppSelect
                                v-if="projects.length"
                                :model-value="selectedProject?.id"
                                :options="projectOptions"
                                :can-clear="false"
                                @update:model-value="switchProject"
                            />
                        </div>
                    </div>
                </section>

                <section
                    class="mt-6 flex flex-col gap-4 overflow-hidden rounded-2xl border border-blue-100 bg-gradient-to-r from-blue-50 via-white to-violet-50 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5"
                >
                    <div class="flex items-center gap-4">
                        <div
                            class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-white text-blue-600 shadow-sm"
                        >
                            <Sparkles class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-900">
                                Lacakeen is ready to work.
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Access your activity, timeline, and team progress from one focused
                                dashboard.
                            </p>
                        </div>
                    </div>
                    <Link
                        :href="route('reporting')"
                        class="ui-button-secondary h-9 shrink-0 border-blue-100 text-blue-600"
                    >
                        View details</Link
                    >
                </section>

                <section class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="card in cards"
                        :key="card.label"
                        class="ui-card p-4 transition hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between">
                            <div
                                class="grid h-9 w-9 place-items-center rounded-xl bg-blue-50 text-blue-600"
                            >
                                <component :is="card.icon" class="h-4 w-4" />
                            </div>
                            <Link
                                :href="card.href"
                                class="flex items-center gap-1 text-[11px] font-semibold text-blue-600"
                            >
                                View details
                                <ArrowRight class="h-3 w-3" />
                            </Link>
                        </div>
                        <p class="mt-5 text-2xl font-bold tracking-tight text-slate-950">
                            {{ card.value }}
                        </p>
                        <p class="mt-1 text-sm text-slate-500">{{ card.label }}</p>
                    </article>
                </section>
            </template>

            <section class="mt-6 ui-card overflow-hidden">
                <div
                    class="flex flex-col gap-3 border-b border-slate-100 p-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex max-w-full gap-1 overflow-x-auto rounded-xl bg-slate-100 p-1">
                        <button
                            v-for="view in views"
                            :key="view.name"
                            class="flex h-9 shrink-0 items-center gap-2 rounded-lg px-3 text-xs font-semibold transition"
                            :class="
                                activeView === view.name
                                    ? 'bg-white text-blue-700 shadow-sm'
                                    : 'text-slate-500 hover:text-slate-800'
                            "
                            @click="activeView = view.name"
                        >
                            <component :is="view.icon" class="h-3.5 w-3.5" />{{ view.name }}
                        </button>
                    </div>
                    <div ref="toolbarRef" class="flex items-center gap-1.5">
                        <details class="relative">
                            <summary
                                class="ui-icon-button h-9 w-9 list-none"
                                title="Tasks by status"
                            >
                                <ChartNoAxesColumn class="h-4 w-4" />
                            </summary>
                            <div
                                class="absolute right-0 z-20 mt-1 w-56 rounded-xl border border-slate-200 bg-white p-2 text-xs shadow-xl"
                            >
                                <p
                                    class="px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-slate-400"
                                >
                                    Tasks by status
                                </p>
                                <div
                                    v-for="stat in statusStats"
                                    :key="stat.name"
                                    class="flex items-center justify-between gap-2 rounded-lg px-2 py-1.5"
                                >
                                    <span class="flex items-center gap-2 text-slate-600">
                                        <span
                                            class="h-2 w-2 shrink-0 rounded-full"
                                            :style="{ backgroundColor: stat.color }"
                                        />{{ stat.name }}</span
                                    ><span class="font-bold text-slate-900">{{ stat.count }}</span>
                                </div>
                                <p v-if="!statusStats.length" class="px-2 py-1.5 text-slate-400">
                                    No columns yet.
                                </p>
                            </div>
                        </details>
                        <details v-if="!['kanban','calendar'].includes(activeView.toLowerCase())" class="relative">
                            <summary class="ui-icon-button h-9 w-9 list-none cursor-pointer" title="Sort tasks">
                                <ArrowDownUp class="h-4 w-4" />
                            </summary>
                            <div
                                class="absolute right-0 z-20 mt-1 w-44 rounded-xl border border-slate-200 bg-white p-1 text-xs shadow-xl"
                            >
                                <button
                                    v-for="option in sortOptions"
                                    :key="option.value"
                                    class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left hover:bg-slate-50"
                                    :class="
                                        sortBy === option.value && 'font-semibold text-blue-600'
                                    "
                                    @click="setSort(option.value)"
                                >
                                    {{ option.label }}
                                    <span v-if="sortBy === option.value" class="text-[10px]">{{
                                        sortDir === 'asc' ? '↑' : '↓'
                                    }}</span>
                                </button>
                            </div>
                        </details>
                        <button
                            class="ui-icon-button h-9 w-9"
                            :class="focusMode && 'border-blue-200 bg-blue-50 text-blue-600'"
                            title="Toggle focus mode"
                            @click="focusMode = !focusMode"
                        >
                            <Bolt class="h-4 w-4" />
                        </button>
                        <div class="relative flex items-center">
                            <input
                                v-if="boardSearchOpen"
                                ref="boardSearchInput"
                                v-model="boardSearch"
                                type="text"
                                placeholder="Search tasks…"
                                class="ui-input h-9 w-40 pr-7 text-xs"
                                @keydown.esc="closeBoardSearch"
                            /><button
                                v-if="boardSearchOpen"
                                class="absolute right-1.5 text-slate-400 hover:text-slate-600"
                                title="Close search"
                                @click="closeBoardSearch"
                            >
                                <X class="h-3.5 w-3.5" /></button
                            ><button
                                v-else-if="activeView.toLowerCase() !== 'calendar'"
                                class="ui-icon-button h-9 w-9"
                                title="Search tasks"
                                @click="openBoardSearch"
                            >
                                <Search class="h-4 w-4" />
                            </button>
                        </div>
                        <details class="relative">
                            <summary class="ui-icon-button h-9 w-9 list-none cursor-pointer" title="More">
                                <Ellipsis class="h-4 w-4" />
                            </summary>
                            <div
                                class="absolute right-0 z-20 mt-1 w-44 rounded-xl border border-slate-200 bg-white p-1 text-xs shadow-xl"
                            >
                                <button
                                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left hover:bg-slate-50"
                                    @click="exportCsv"
                                >
                                    <Download class="h-3.5 w-3.5" />Export CSV
                                </button>
                                <button
                                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left hover:bg-slate-50"
                                    @click="refreshBoard"
                                >
                                    <RefreshCw class="h-3.5 w-3.5" />Refresh board
                                </button>
                            </div>
                        </details>
                        <button
                            class="ui-button-primary h-9 px-3"
                            @click="addTask(statuses[0]?.id)"
                        >
                            <Plus class="h-4 w-4" />New
                        </button>
                    </div>
                </div>

                <div class="p-3 sm:p-4">
                    <KanbanBoard
                        v-if="activeView === 'Kanban'"
                        :statuses="statuses"
                        :project="selectedProject"
                        :search-query="boardSearch"
                        @open-task="openTask"
                        @add-task="addTask"
                        @add-column="addColumn"
                        ><template #empty>
                            <EmptyState
                                title="No board yet"
                                description="Create a project first, then add its workflow columns."
                            >
                                <Link :href="route('projects.index')" class="ui-button-primary"
                                    >Open projects</Link
                                >
                            </EmptyState>
                        </template>
                    </KanbanBoard>

                    <div v-else-if="activeView === 'Spreadsheet'" class="overflow-x-auto">
                        <table class="w-full min-w-[800px] text-left text-sm">
                            <thead>
                                <tr
                                    class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-400 [&>th]:whitespace-nowrap"
                                >
                                    <th class="px-3 py-3">Code</th>
                                    <th class="px-3 py-3">Task</th>
                                    <th class="px-3 py-3">Status</th>
                                    <th class="px-3 py-3">Priority</th>
                                    <th class="px-3 py-3">Assignees</th>
                                    <th class="px-3 py-3">Due date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="task in sortedTasks"
                                    :key="task.id"
                                    class="cursor-pointer border-b border-slate-100 hover:bg-slate-50"
                                    @click="openTask(task)"
                                >
                                    <td
                                        class="px-3 py-4 font-semibold text-slate-400 whitespace-nowrap"
                                    >
                                        {{ task.code }}
                                    </td>
                                    <td class="px-3 py-4 font-semibold text-slate-800">
                                        {{ task.title }}
                                    </td>
                                    <td class="px-3 py-4">
                                        <div class="flex items-center gap-2 whitespace-nowrap">
                                            <span class="h-2 w-2 rounded-full" :style="`background-color: ${task.status?.color};`"></span>
                                            <span class="text-slate-600">{{ task.status?.name}}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 capitalize">{{ task.priority }}</td>
                                    <td class="px-3 py-4">
                                        <AvatarStack :users="task.assignees" />
                                    </td>
                                    <td class="px-3 py-4 text-slate-500 whitespace-nowrap">
                                        {{ formatTaskDate(task.due_date) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <EmptyState
                            v-if="!sortedTasks.length"
                            :title="boardSearch ? 'No matching tasks' : 'No tasks found'"
                        />
                    </div>

                    <div
                        v-else-if="activeView === 'Timeline'"
                        class="space-y-3 overflow-x-auto p-2"
                    >
                        <div
                            v-for="task in sortedTasks"
                            :key="task.id"
                            class="grid min-w-[680px] grid-cols-[220px_1fr] items-center gap-4"
                        >
                            <button
                                class="truncate text-left text-sm font-semibold text-slate-700"
                                @click="openTask(task)"
                            >
                                {{ task.code }} · {{ task.title }}
                            </button>
                            <div class="relative h-9 rounded-lg bg-slate-100">
                                <div
                                    class="absolute inset-y-1 flex items-center rounded-md px-3 text-[11px] font-semibold text-white shadow-sm"
                                    :style="{
                                        left: `${((task.order ?? 0) * 7) % 55}%`,
                                        width: '35%',
                                        backgroundColor: task.project.color,
                                    }"
                                >
                                    {{ formatTaskDate(task.due_date) }}
                                </div>
                            </div>
                        </div>
                        <EmptyState
                            v-if="!sortedTasks.length"
                            :title="boardSearch ? 'No matching tasks' : 'Nothing on the timeline'"
                        />
                    </div>

                    <div
                        v-else
                        class="grid min-h-[420px] grid-cols-2 gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 sm:grid-cols-4 lg:grid-cols-7"
                    >
                        <div v-for="day in 28" :key="day" class="min-h-28 bg-white p-2">
                            <span class="text-xs font-semibold text-slate-400">{{ day }}</span
                            ><button
                                v-for="task in sortedTasks.filter(
                                    (item) => Number(item.due_date?.slice(8, 10)) === day
                                )"
                                :key="task.id"
                                class="mt-1 block w-full truncate rounded-md bg-blue-50 px-2 py-1 text-left text-[10px] font-semibold text-blue-700"
                                @click="openTask(task)"
                            >
                                {{ task.code }}
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <TaskCreateDialog
            :open="createOpen"
            :defaults="createDefaults"
            @close="createOpen = false"
        />
        <TaskDetailDrawer
            :open="detailOpen"
            :task="selectedTask"
            :statuses="statuses"
            @close="detailOpen = false"
        />
    </AppLayout>
</template>
