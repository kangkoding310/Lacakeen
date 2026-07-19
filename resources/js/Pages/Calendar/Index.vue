<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import AppSelect from '@/Components/ui/AppSelect.vue';
import Modal from '@/Components/ui/Modal.vue';
import TaskDetailDrawer from '@/Components/TaskDetailDrawer.vue';
import { calendarService } from '@/services/calendarService';
import { useTaskComposer } from '@/composables/useTaskComposer';
import type { CalendarEventItem, CalendarFilters } from '@/types/calendarEvent';
import type { Task, TaskProjectRef } from '@/types/task';
import FullCalendar from '@fullcalendar/vue3';
import type { CalendarOptions } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import { Head, router, useForm } from '@inertiajs/vue3';
import { CalendarPlus, Filter, Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface CalendarMember {
    id: number;
    name: string;
    avatar: string | null;
}

interface FullCalendarEventApi {
    id: string;
    title: string;
    start: Date | null;
    extendedProps: { type: string; description?: string | null };
}

const props = defineProps<{
    events: CalendarEventItem[];
    projects: TaskProjectRef[];
    members: CalendarMember[];
    filters: CalendarFilters;
    task?: Task | null;
}>();
const { findProject } = useTaskComposer();
const createOpen = ref(false);
const selectedEvent = ref<FullCalendarEventApi | null>(null);
const taskDrawerOpen = ref(false);
const taskStatuses = computed(() => findProject(props.task?.project_id)?.statuses ?? []);
const query = useForm({
    project: props.filters.project || '',
    assignee: props.filters.assignee || '',
});
const form = useForm({
    title: '',
    description: '',
    project_id: '',
    start_at: '',
    end_at: '',
    all_day: true,
});
const projectOptions = computed(() =>
    props.projects.map((project) => ({ value: project.id, label: project.name }))
);
const memberOptions = computed(() =>
    props.members.map((member) => ({ value: member.id, label: member.name }))
);
const options: CalendarOptions = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    events: props.events,
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay',
    },
    selectable: true,
    height: 'auto',
    dayMaxEvents: 3,
    dateClick: ({ dateStr }) => {
        form.start_at = dateStr;
        form.end_at = dateStr;
        createOpen.value = true;
    },
    eventClick: ({ event }) => {
        const extendedProps = event.extendedProps as { type: string; description?: string | null };
        if (extendedProps.type === 'task') openTask(event.id);
        else
            selectedEvent.value = {
                id: event.id,
                title: event.title,
                start: event.start,
                extendedProps,
            };
    },
};
const openTask = (taskId: string) =>
    router.get(
        route('calendar'),
        { ...query.data(), task: taskId },
        {
            only: ['task'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onSuccess: () => (taskDrawerOpen.value = true),
        }
    );
const closeTaskDrawer = () => (taskDrawerOpen.value = false);
const submit = () =>
    form.post(route('calendar.events.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            createOpen.value = false;
        },
    });
const applyFilters = () => calendarService.list(query.data(), { preserveState: true });
</script>

<template>
    <Head title="Calendar" />
    <AppLayout title="Calendar"
        ><div class="page-shell">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Team calendar</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        See task deadlines and scheduled events in one place.
                    </p>
                </div>
                <button class="ui-button-primary" @click="createOpen = true">
                    <CalendarPlus class="h-4 w-4" />New event
                </button>
            </div>
            <form class="mt-6 flex flex-wrap gap-3" @submit.prevent="applyFilters">
                <AppSelect
                    v-model="query.project"
                    :options="projectOptions"
                    placeholder="All projects"
                    class="w-52"
                /><AppSelect
                    v-model="query.assignee"
                    :options="memberOptions"
                    placeholder="All assignees"
                    class="w-52"
                /><button class="ui-button-secondary"><Filter class="h-4 w-4" />Apply</button>
            </form>
            <div class="ui-card mt-5 overflow-x-auto p-3 sm:p-5">
                <div class="min-w-[720px]"><FullCalendar :options="options" /></div>
            </div>
        </div>
        <Modal :open="createOpen" title="Create calendar event" @close="createOpen = false"
            ><form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label class="ui-label">Event title</label
                    ><input v-model="form.title" class="ui-input" required />
                </div>
                <div>
                    <label class="ui-label">Project</label
                    ><AppSelect
                        v-model="form.project_id"
                        :options="projectOptions"
                        placeholder="No project"
                    />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="ui-label">Starts</label
                        ><input
                            v-model="form.start_at"
                            :type="form.all_day ? 'date' : 'datetime-local'"
                            class="ui-input"
                            required
                        />
                    </div>
                    <div>
                        <label class="ui-label">Ends</label
                        ><input
                            v-model="form.end_at"
                            :type="form.all_day ? 'date' : 'datetime-local'"
                            class="ui-input"
                        />
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm"
                    ><input
                        v-model="form.all_day"
                        type="checkbox"
                        class="rounded border-slate-300 text-blue-600"
                    />All-day event</label
                >
                <div>
                    <label class="ui-label">Description</label
                    ><textarea v-model="form.description" class="ui-input min-h-24 py-2" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" class="ui-button-secondary" @click="createOpen = false">
                        Cancel</button
                    ><button class="ui-button-primary" :disabled="form.processing">
                        <Plus class="h-4 w-4" />Create event
                    </button>
                </div>
            </form></Modal
        >
        <Modal :open="!!selectedEvent" :title="selectedEvent?.title" @close="selectedEvent = null"
            ><p class="text-sm leading-6 text-slate-600">
                {{ selectedEvent?.extendedProps.description || 'No description.' }}
            </p>
            <p class="mt-4 text-xs text-slate-400">
                Starts {{ selectedEvent?.start?.toLocaleString() }}
            </p></Modal
        >
        <TaskDetailDrawer
            :open="taskDrawerOpen"
            :task="task ?? null"
            :statuses="taskStatuses"
            @close="closeTaskDrawer"
        />
    </AppLayout>
</template>
