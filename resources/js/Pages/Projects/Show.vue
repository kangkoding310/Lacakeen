<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppSelect from '@/Components/ui/AppSelect.vue';
import AvatarStack from '@/Components/ui/AvatarStack.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import KanbanBoard from '@/Components/KanbanBoard.vue';
import Modal from '@/Components/ui/Modal.vue';
import TaskCreateDialog from '@/Components/TaskCreateDialog.vue';
import TaskDetailDrawer from '@/Components/TaskDetailDrawer.vue';
import { formatDate } from '@/lib/date';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Archive, CalendarDays, ChartNoAxesGantt, CircleGauge, Kanban, List, MoreHorizontal, Pencil, Plus, Search, Trash2, UserMinus, Users } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({ project: Object, statuses: Array, tasks: Array, events: Array, availableMembers: Array, tab: String });
const page = usePage();
const activeTab = ref(['summary', 'kanban', 'list', 'calendar', 'timeline'].includes(props.tab) ? props.tab : 'summary');
const search = ref('');
const createOpen = ref(false);
const statusOpen = ref(false);
const createDefaults = ref({ project_id: props.project.id });
const selectedTask = ref(null);
const editOpen = ref(false);
const menuOpen = ref(false);
const menuElement = ref(null);
const member = useForm({ user_id: null, role_in_project: 'editor' });
const edit = useForm({ name: props.project.name, prefix: props.project.prefix, description: props.project.description || '', color: props.project.color });
const statusForm = useForm({ name: '', color: '#64748B' });
const tabs = [
    { id: 'summary', label: 'Summary', icon: CircleGauge }, { id: 'kanban', label: 'Kanban', icon: Kanban },
    { id: 'list', label: 'List', icon: List }, { id: 'calendar', label: 'Calendar', icon: CalendarDays },
    { id: 'timeline', label: 'Timeline', icon: ChartNoAxesGantt },
];
const memberOptions = computed(() => props.availableMembers.map((user) => ({ value: user.id, label: `${user.name} ${user.email}` })));
const roleOptions = [{ value: 'owner', label: 'Owner' }, { value: 'editor', label: 'Editor' }, { value: 'viewer', label: 'Viewer' }];
const currentMembership = computed(() => props.project.members.find((user) => user.id === page.props.auth.user.id));
const isAdmin = computed(() => page.props.auth.user.roles.some((role) => role.name === 'admin'));
const canManage = computed(() => isAdmin.value || ['owner', 'editor'].includes(currentMembership.value?.pivot?.role_in_project));
const canDelete = computed(() => isAdmin.value || currentMembership.value?.pivot?.role_in_project === 'owner');
const filteredTasks = computed(() => props.tasks.filter((task) => `${task.code} ${task.title}`.toLowerCase().includes(search.value.toLowerCase())));
const boardStatuses = computed(() => props.statuses.map((status) => ({ ...status, tasks: status.tasks.filter((task) => filteredTasks.value.some((match) => match.id === task.id)) })));
const completed = computed(() => props.tasks.filter((task) => task.status?.name.toLowerCase() === 'completed').length);
const completion = computed(() => props.tasks.length ? Math.round(completed.value / props.tasks.length * 100) : 0);
const overdue = computed(() => props.tasks.filter((task) => task.due_date && new Date(task.due_date) < new Date() && task.status?.name.toLowerCase() !== 'completed').length);
const timelineStart = computed(() => { const dates = props.tasks.flatMap((task) => [task.start_date, task.due_date]).filter(Boolean).map(Date.parse); return dates.length ? Math.min(...dates) : Date.now(); });
const timelineEnd = computed(() => { const dates = props.tasks.flatMap((task) => [task.start_date, task.due_date]).filter(Boolean).map(Date.parse); return dates.length ? Math.max(...dates) : Date.now() + 86400000; });
const timelineStyle = (task) => { const total = Math.max(timelineEnd.value - timelineStart.value, 86400000); const start = Date.parse(task.start_date || task.due_date) || timelineStart.value; const end = Date.parse(task.due_date || task.start_date) || start + 86400000; return { left: `${Math.max(0, (start - timelineStart.value) / total * 100)}%`, width: `${Math.max(3, (end - start) / total * 100)}%`, backgroundColor: task.status?.color || props.project.color }; };
const calendarOptions = computed(() => ({ plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin], initialView: 'dayGridMonth', events: props.events, headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek' }, height: 'auto', dayMaxEvents: 3, eventClick: ({ event }) => { selectedTask.value = props.tasks.find((task) => task.id === event.id); } }));
const selectTab = (id) => { activeTab.value = id; window.history.replaceState({}, '', route('projects.show', { project: props.project.id, tab: id })); };
const openCreate = (statusId = '') => { createDefaults.value = { project_id: props.project.id, status_id: statusId }; createOpen.value = true; };
const addStatus = () => statusForm.post(route('statuses.store', props.project.id), { preserveScroll: true, onSuccess: () => { statusForm.reset(); statusOpen.value = false; } });
const addMember = () => member.post(route('projects.members.store', props.project.id), { preserveScroll: true, onSuccess: () => member.reset() });
const updateRole = (user, role) => router.patch(route('projects.members.update', [props.project.id, user.id]), { role_in_project: role }, { preserveScroll: true });
const removeMember = (user) => { if (confirm(`Remove ${user.name} from this project?`)) router.delete(route('projects.members.destroy', [props.project.id, user.id]), { preserveScroll: true }); };
const saveProject = () => edit.patch(route('projects.update', props.project.id), { preserveScroll: true, onSuccess: () => { editOpen.value = false; } });
const toggleArchive = () => router.patch(route('projects.update', props.project.id), { status: props.project.status === 'active' ? 'archived' : 'active' });
const deleteProject = () => { if (confirm(`Permanently delete “${props.project.name}” and all its tasks?`)) router.delete(route('projects.destroy', props.project.id)); };
const prettyDate = (date) => formatDate(date, { month: 'short', day: '2-digit', year: '2-digit' }, 'No date');
watch(() => props.tasks, (tasks) => { if (selectedTask.value) selectedTask.value = tasks.find((task) => task.id === selectedTask.value.id) || selectedTask.value; });
const closeMenu = (event) => {
    if (event.key === 'Escape' || (event.type === 'pointerdown' && !menuElement.value?.contains(event.target))) menuOpen.value = false;
};
onMounted(() => { document.addEventListener('pointerdown', closeMenu); document.addEventListener('keydown', closeMenu); });
onBeforeUnmount(() => { document.removeEventListener('pointerdown', closeMenu); document.removeEventListener('keydown', closeMenu); });
</script>

<template>

    <Head :title="project.name" />
    <AppLayout :title="project.name" section="Projects" :fullscreen="activeTab === 'kanban'">
        <div :class="activeTab === 'kanban' && 'flex h-full min-h-0 flex-col overflow-hidden'">
        <div class="shrink-0 border-b border-slate-200 bg-white px-4 pt-2 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-[1600px]">
                <div class="flex h-11 items-center justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-3"><span
                            class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-[10px] font-black text-white"
                            :style="{ backgroundColor: project.color }">{{ project.prefix.slice(0, 2) }}</span>
                        <div class="min-w-0">
                            <h1 class="truncate text-base font-bold leading-tight text-slate-950">{{ project.name }}</h1>
                            <p class="text-[10px] font-semibold text-slate-400">{{ project.prefix }} · {{ project.tasks_count }} tasks</p>
                        </div>
                    </div>
                    <div ref="menuElement" class="relative"><button class="ui-icon-button" aria-label="Project actions"
                            title="Project actions" @click="menuOpen = !menuOpen">
                            <MoreHorizontal class="h-5 w-5" />
                        </button>
                        <div v-if="menuOpen"
                            class="absolute right-0 top-12 z-30 w-44 rounded-xl border border-slate-200 bg-white p-1 text-xs font-semibold shadow-xl">
                            <button v-if="canManage"
                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-50"
                                @click="editOpen = true; menuOpen = false">
                                <Pencil class="h-4 w-4" />Edit project
                            </button><button v-if="canManage"
                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-50"
                                @click="toggleArchive">
                                <Archive class="h-4 w-4" />{{ project.status === 'active' ? 'Archive' : 'Restore' }}
                            </button><button v-if="canDelete"
                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-red-600 hover:bg-red-50"
                                @click="deleteProject">
                                <Trash2 class="h-4 w-4" />Delete project
                            </button></div>
                    </div>
                </div>
                <nav class="mt-1 flex gap-1 overflow-x-auto"><button v-for="item in tabs" :key="item.id"
                        class="flex shrink-0 items-center gap-2 border-b-2 px-3 py-2 text-xs font-semibold transition"
                        :class="activeTab === item.id ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-900'"
                        @click="selectTab(item.id)">
                        <component :is="item.icon" class="h-4 w-4" />{{ item.label }}
                    </button></nav>
            </div>
        </div>

        <div :class="activeTab === 'kanban' ? 'flex min-h-0 flex-1 flex-col px-4 py-3 sm:px-6 lg:px-8' : 'page-shell'">

            <template v-if="activeTab === 'summary'">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div v-for="stat in [{ label: 'Total tasks', value: tasks.length, color: 'text-blue-600' }, { label: 'Completed', value: completed, color: 'text-emerald-600' }, { label: 'Completion', value: `${completion}%`, color: 'text-violet-600' }, { label: 'Overdue', value: overdue, color: 'text-red-600' }]"
                        :key="stat.label" class="ui-card p-5">
                        <p class="text-xs font-semibold text-slate-400">{{ stat.label }}</p>
                        <p class="mt-2 text-3xl font-bold" :class="stat.color">{{ stat.value }}</p>
                    </div>
                </div>
                <div class="mt-5 grid gap-5 xl:grid-cols-[1.2fr_.8fr]">
                    <section class="ui-card p-5">
                        <h2 class="font-bold">Status overview</h2>
                        <div class="mt-5 space-y-4">
                            <div v-for="status in statuses" :key="status.id">
                                <div class="mb-1.5 flex justify-between text-xs font-semibold"><span
                                        class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full"
                                            :style="{ backgroundColor: status.color }" />{{ status.name }}</span><span
                                        class="text-slate-400">{{ status.tasks.length }}</span></div>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full"
                                        :style="{ width: `${tasks.length ? status.tasks.length / tasks.length * 100 : 0}%`, backgroundColor: status.color }" />
                                </div>
                            </div>
                        </div>
                    </section>
                    <section class="ui-card overflow-hidden">
                        <div class="border-b border-slate-100 p-5">
                            <h2 class="font-bold">Project members</h2>
                            <p class="mt-1 text-xs text-slate-400">Manage access and project roles.</p>
                        </div>
                        <form v-if="canManage && availableMembers.length"
                            class="grid gap-2 border-b border-slate-100 bg-slate-50/60 p-3 sm:grid-cols-[1fr_130px_auto]"
                            @submit.prevent="addMember">
                            <AppSelect v-model="member.user_id" :options="memberOptions" placeholder="Choose member" />
                            <AppSelect v-model="member.role_in_project" :options="roleOptions.slice(1)"
                                :searchable="false" :can-clear="false" /><button class="ui-button-primary px-3"
                                :disabled="!member.user_id">
                                <Plus class="h-4 w-4" />
                            </button>
                        </form>
                        <div class="max-h-80 divide-y divide-slate-100 overflow-y-auto">
                            <div v-for="user in project.members" :key="user.id" class="flex items-center gap-3 p-3"><img
                                    :src="user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}`" class="h-9 w-9 rounded-xl object-cover" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-bold">{{ user.name }}</p>
                                    <p class="truncate text-[10px] text-slate-400">{{ user.email }}</p>
                                </div>
                                <AppSelect v-if="canManage && user.id !== project.created_by"
                                    :model-value="user.pivot.role_in_project" :options="roleOptions" :searchable="false"
                                    :can-clear="false" class="w-28" @update:model-value="updateRole(user, $event)" />
                                <span v-else class="text-[10px] font-bold capitalize text-slate-400">{{
                                    user.pivot.role_in_project }}</span><button
                                    v-if="canManage && user.id !== project.created_by"
                                    class="text-red-400 hover:text-red-600" title="Remove member"
                                    @click="removeMember(user)">
                                    <UserMinus class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </section>
                </div>
            </template>

            <KanbanBoard v-else-if="activeTab === 'kanban'" :statuses="boardStatuses" :project="project" contained class="min-h-0 flex-1"
                @open-task="selectedTask = $event" @add-task="openCreate($event)" @add-column="statusOpen = true"><template #empty>
                    <EmptyState title="No workflow yet" description="Add a status column to begin." />
                </template>
            </KanbanBoard>

            <div v-else-if="activeTab === 'list'" class="table-shell overflow-x-auto">
                <table class="w-full min-w-[850px] text-left">
                    <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Task</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3">Priority</th>
                            <th class="px-3 py-3">Assignees</th>
                            <th class="px-3 py-3">Start</th>
                            <th class="px-3 py-3">Due</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="task in filteredTasks" :key="task.id"
                            class="cursor-pointer text-sm hover:bg-slate-50" @click="selectedTask = task">
                            <td class="px-4 py-4"><span class="text-[10px] font-bold text-blue-600">{{ task.code
                                    }}</span>
                                <p class="font-semibold">{{ task.title }}</p>
                            </td>
                            <td class="px-3 py-4"><span class="rounded-lg px-2 py-1 text-xs font-semibold"
                                    :style="{ color: task.status.color, backgroundColor: `${task.status.color}12` }">{{
                                    task.status.name }}</span></td>
                            <td class="px-3 py-4 text-xs font-bold capitalize">{{ task.priority }}</td>
                            <td class="px-3 py-4">
                                <AvatarStack :users="task.assignees" />
                            </td>
                            <td class="px-3 py-4 text-xs text-slate-500">{{ prettyDate(task.start_date) }}</td>
                            <td class="px-3 py-4 text-xs text-slate-500">{{ prettyDate(task.due_date) }}</td>
                        </tr>
                    </tbody>
                </table>
                <EmptyState v-if="!filteredTasks.length" title="No matching tasks"
                    description="Try another search or create a task." />
            </div>

            <div v-else-if="activeTab === 'calendar'" class="ui-card overflow-x-auto p-4">
                <div class="min-w-[720px]">
                    <FullCalendar :options="calendarOptions" />
                </div>
            </div>

            <div v-else class="ui-card overflow-x-auto">
                <div class="min-w-[850px]">
                    <div
                        class="grid grid-cols-[220px_1fr] border-b border-slate-200 bg-slate-50 text-xs font-bold text-slate-500">
                        <div class="p-4">Task</div>
                        <div class="flex justify-between border-l border-slate-200 p-4"><span>{{
                                prettyDate(timelineStart)
                                }}</span><span>Project schedule</span><span>{{ prettyDate(timelineEnd) }}</span></div>
                    </div>
                    <div v-for="task in filteredTasks" :key="task.id"
                        class="grid grid-cols-[220px_1fr] border-b border-slate-100 text-xs last:border-0"><button
                            class="truncate p-4 text-left font-semibold hover:text-blue-600"
                            @click="selectedTask = task">{{
                            task.code }} · {{ task.title }}</button>
                        <div class="relative m-3 min-h-7 rounded-lg bg-slate-50"><button
                                class="absolute top-1 h-5 min-w-6 rounded-md px-2 text-[10px] font-bold text-white shadow-sm"
                                :style="timelineStyle(task)"
                                :title="`${prettyDate(task.start_date)} – ${prettyDate(task.due_date)}`"
                                @click="selectedTask = task">{{ task.code }}</button></div>
                    </div>
                    <EmptyState v-if="!filteredTasks.length" title="No timeline data"
                        description="Add task start and due dates to plan the schedule." />
                </div>
            </div>
        </div>

        <TaskCreateDialog :open="createOpen" :defaults="createDefaults" @close="createOpen = false" />
        <TaskDetailDrawer :open="!!selectedTask" :task="selectedTask" :statuses="statuses"
            @close="selectedTask = null" />
        <Modal :open="statusOpen" title="Add column" description="Create a new status for this project." @close="statusOpen = false">
            <form class="space-y-4" @submit.prevent="addStatus"><div><label class="ui-label">Status name</label><input v-model="statusForm.name" class="ui-input" required maxlength="80" placeholder="e.g. In Progress" /></div><div><label class="ui-label">Color</label><input v-model="statusForm.color" type="color" class="ui-input p-1" /></div><p v-if="statusForm.errors.name" class="text-xs text-red-600">{{ statusForm.errors.name }}</p><div class="flex justify-end gap-2"><button type="button" class="ui-button-secondary" @click="statusOpen = false">Cancel</button><button class="ui-button-primary" :disabled="statusForm.processing">{{ statusForm.processing ? 'Adding…' : 'Add column' }}</button></div></form>
        </Modal>
        <Modal :open="editOpen" title="Edit project" @close="editOpen = false">
            <form class="space-y-4" @submit.prevent="saveProject">
                <div><label class="ui-label">Project name</label><input v-model="edit.name" class="ui-input" required />
                </div>
                <div class="grid grid-cols-[1fr_80px] gap-3">
                    <div><label class="ui-label">Prefix</label><input v-model="edit.prefix" class="ui-input uppercase"
                            maxlength="12" required @input="edit.prefix = edit.prefix.replace(/[^a-zA-Z0-9]/g, '')" /></div>
                    <div><label class="ui-label">Color</label><input v-model="edit.color" type="color"
                            class="ui-input p-1" />
                    </div>
                </div>
                <div><label class="ui-label">Description</label><textarea v-model="edit.description"
                        class="ui-input min-h-24 py-2" /></div>
                <div class="flex justify-end gap-2"><button type="button" class="ui-button-secondary"
                        @click="editOpen = false">Cancel</button><button class="ui-button-primary"
                        :disabled="edit.processing">Save changes</button></div>
            </form>
        </Modal>
        </div>
    </AppLayout>
</template>
