<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AvatarStack from '@/Components/ui/AvatarStack.vue';
import AppSelect from '@/Components/ui/AppSelect.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import KanbanBoard from '@/Components/KanbanBoard.vue';
import TaskCreateDialog from '@/Components/TaskCreateDialog.vue';
import TaskDetailDrawer from '@/Components/TaskDetailDrawer.vue';
import { formatDate } from '@/lib/date';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowDownUp, ArrowRight, Bolt, CalendarDays, ChartNoAxesColumn, CheckCircle2,
    ChevronsUpDown, CircleUserRound, Ellipsis, FolderKanban, LayoutGrid, Plus,
    Search, SlidersHorizontal, Sparkles, Table2, Users,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({ stats: Object, projects: Array, selectedProject: Object, statuses: Array, members: Array });
const activeView = ref('Kanban');
const selectedTask = ref(null);
const detailOpen = ref(false);
const createOpen = ref(false);
const createDefaults = ref({});
const allTasks = computed(() => props.statuses.flatMap((status) => status.tasks || []));
const projectOptions = computed(() => props.projects.map((project) => ({ value: project.id, label: project.name })));
const views = [{ name: 'Kanban', icon: LayoutGrid }, { name: 'Timeline', icon: ChartNoAxesColumn }, { name: 'Spreadsheet', icon: Table2 }, { name: 'Calendar', icon: CalendarDays }];
const cards = computed(() => [
    { label: 'Active employees', value: props.stats.activeEmployees, icon: Users, href: route('members') },
    { label: 'Active projects', value: props.stats.activeProjects, icon: FolderKanban, href: route('projects.index') },
    { label: 'Number of tasks', value: props.stats.tasks, icon: Table2, href: route('tasks.index') },
    { label: 'Task accomplished', value: `${props.stats.accomplished}%`, icon: CheckCircle2, href: route('reporting') },
]);
const openTask = (task) => { selectedTask.value = task; detailOpen.value = true; };
const addTask = (statusId) => { createDefaults.value = { project_id: props.selectedProject?.id, status_id: statusId }; createOpen.value = true; };
const addColumn = () => { const name = prompt('New column name'); if (name?.trim()) router.post(route('statuses.store', props.selectedProject.id), { name }, { preserveScroll: true }); };
const switchProject = (project) => router.get(route('dashboard'), { project }, { preserveState: false, preserveScroll: true });
const formatTaskDate = (date) => formatDate(date, { month: 'short', day: '2-digit', year: '2-digit' });
</script>

<template>

    <Head title="Overview" />
    <AppLayout title="Overview">
        <div class="page-shell">
            <section class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                <div>
                    <p class="mb-2 text-xs font-bold uppercase tracking-[.18em] text-blue-600">Thursday focus</p>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Welcome back, {{
                        $page.props.auth.user.name.split(' ')[0] }}!</h1>
                    <p class="mt-2 text-sm text-slate-500">Stay on top of your tasks, monitor progress, and track
                        status.</p>
                </div>
                <div class="flex items-center gap-3">
                    <AvatarStack :users="members" :max="4" size="md" />
                    <div class="w-52">
                        <AppSelect v-if="projects.length" :model-value="selectedProject?.id" :options="projectOptions"
                        :can-clear="false" @update:model-value="switchProject" />
                    </div>
                </div>
            </section>

            <section
                class="mt-6 flex flex-col gap-4 overflow-hidden rounded-2xl border border-blue-100 bg-gradient-to-r from-blue-50 via-white to-violet-50 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <div class="flex items-center gap-4">
                    <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-white text-blue-600 shadow-sm">
                        <Sparkles class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-900">Lacakeen is ready to work.</p>
                        <p class="mt-1 text-xs text-slate-500">Access your activity, timeline, and team progress from
                            one focused dashboard.</p>
                    </div>
                </div>
                <Link :href="route('reporting')" class="ui-button-secondary h-9 shrink-0 border-blue-100 text-blue-600">
                    View details</Link>
            </section>

            <section class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article v-for="card in cards" :key="card.label"
                    class="ui-card p-4 transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex items-start justify-between">
                        <div class="grid h-9 w-9 place-items-center rounded-xl bg-blue-50 text-blue-600">
                            <component :is="card.icon" class="h-4 w-4" />
                        </div>
                        <Link :href="card.href" class="flex items-center gap-1 text-[11px] font-semibold text-blue-600">
                            View details
                            <ArrowRight class="h-3 w-3" />
                        </Link>
                    </div>
                    <p class="mt-5 text-2xl font-bold tracking-tight text-slate-950">{{ card.value }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ card.label }}</p>
                </article>
            </section>

            <section class="mt-6 ui-card overflow-hidden">
                <div
                    class="flex flex-col gap-3 border-b border-slate-100 p-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex max-w-full gap-1 overflow-x-auto rounded-xl bg-slate-100 p-1"><button
                            v-for="view in views" :key="view.name"
                            class="flex h-9 shrink-0 items-center gap-2 rounded-lg px-3 text-xs font-semibold transition"
                            :class="activeView === view.name ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                            @click="activeView = view.name">
                            <component :is="view.icon" class="h-3.5 w-3.5" />{{ view.name }}
                        </button></div>
                    <div class="flex items-center gap-1.5"><button class="ui-icon-button h-9 w-9">
                            <ChartNoAxesColumn class="h-4 w-4" />
                        </button><button class="ui-icon-button h-9 w-9">
                            <ArrowDownUp class="h-4 w-4" />
                        </button><button class="ui-icon-button h-9 w-9">
                            <Bolt class="h-4 w-4" />
                        </button>
                        <Link :href="route('tasks.index')" class="ui-icon-button h-9 w-9">
                            <Search class="h-4 w-4" />
                        </Link><button class="ui-icon-button h-9 w-9">
                            <Ellipsis class="h-4 w-4" />
                        </button><button class="ui-button-primary h-9 px-3" @click="addTask(statuses[0]?.id)">
                            <Plus class="h-4 w-4" />New
                        </button>
                    </div>
                </div>

                <div class="p-3 sm:p-4">
                    <KanbanBoard v-if="activeView === 'Kanban'" :statuses="statuses" :project="selectedProject"
                        @open-task="openTask"
                        @add-task="($event, isColumn) => isColumn ? addColumn() : addTask($event)"><template #empty>
                            <EmptyState title="No board yet"
                                description="Create a project first, then add its workflow columns.">
                                <Link :href="route('projects.index')" class="ui-button-primary">Open projects</Link>
                            </EmptyState>
                        </template>
                    </KanbanBoard>

                    <div v-else-if="activeView === 'Spreadsheet'" class="overflow-x-auto">
                        <table class="w-full min-w-[800px] text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-400">
                                    <th class="px-3 py-3">Code</th>
                                    <th class="px-3 py-3">Task</th>
                                    <th class="px-3 py-3">Status</th>
                                    <th class="px-3 py-3">Priority</th>
                                    <th class="px-3 py-3">Assignees</th>
                                    <th class="px-3 py-3">Due date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="task in allTasks" :key="task.id"
                                    class="cursor-pointer border-b border-slate-100 hover:bg-slate-50"
                                    @click="openTask(task)">
                                    <td class="px-3 py-4 font-semibold text-slate-400">{{ task.code }}</td>
                                    <td class="px-3 py-4 font-semibold text-slate-800">{{ task.title }}</td>
                                    <td class="px-3 py-4"><span class="rounded-lg bg-slate-100 px-2 py-1 text-xs">{{
                                            task.status?.name
                                            }}</span></td>
                                    <td class="px-3 py-4 capitalize">{{ task.priority }}</td>
                                    <td class="px-3 py-4">
                                        <AvatarStack :users="task.assignees" />
                                    </td>
                                    <td class="px-3 py-4 text-slate-500">{{ formatTaskDate(task.due_date) }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <EmptyState v-if="!allTasks.length" title="No tasks found" />
                    </div>

                    <div v-else-if="activeView === 'Timeline'" class="space-y-3 overflow-x-auto p-2">
                        <div v-for="task in allTasks" :key="task.id"
                            class="grid min-w-[680px] grid-cols-[220px_1fr] items-center gap-4">
                            <button class="truncate text-left text-sm font-semibold text-slate-700"
                                @click="openTask(task)">{{ task.code }}
                                · {{ task.title }}</button>
                            <div class="relative h-9 rounded-lg bg-slate-100">
                                <div class="absolute inset-y-1 flex items-center rounded-md px-3 text-[11px] font-semibold text-white shadow-sm"
                                    :style="{ left: `${(task.order * 7) % 55}%`, width: '35%', backgroundColor: task.project.color }">
                                    {{
                                    formatTaskDate(task.due_date) }}</div>
                            </div>
                        </div>
                        <EmptyState v-if="!allTasks.length" title="Nothing on the timeline" />
                    </div>

                    <div v-else
                        class="grid min-h-[420px] grid-cols-2 gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 sm:grid-cols-4 lg:grid-cols-7">
                        <div v-for="day in 28" :key="day" class="min-h-28 bg-white p-2"><span
                                class="text-xs font-semibold text-slate-400">{{ day }}</span><button
                                v-for="task in allTasks.filter(item => Number(item.due_date?.slice(8, 10)) === day)"
                                :key="task.id"
                                class="mt-1 block w-full truncate rounded-md bg-blue-50 px-2 py-1 text-left text-[10px] font-semibold text-blue-700"
                                @click="openTask(task)">{{ task.code }}</button></div>
                    </div>
                </div>
            </section>
        </div>

        <TaskCreateDialog :open="createOpen" :defaults="createDefaults" @close="createOpen = false" />
        <TaskDetailDrawer :open="detailOpen" :task="selectedTask" :statuses="statuses" @close="detailOpen = false" />
    </AppLayout>
</template>
