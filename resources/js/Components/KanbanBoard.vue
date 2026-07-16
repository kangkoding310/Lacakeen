<script setup>
import TaskCard from '@/Components/TaskCard.vue';
import draggable from 'vuedraggable';
import { router } from '@inertiajs/vue3';
import { Check, MoreVertical, Plus, X } from 'lucide-vue-next';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({ statuses: { type: Array, default: () => [] }, project: Object, contained: Boolean });
const emit = defineEmits(['open-task', 'add-task', 'add-column']);
const columns = ref([]);
const board = ref(null);
const editingId = ref(null);
const editingName = ref('');
const editInput = ref(null);
const setEditInput = (element) => { editInput.value = element; };
watch(() => props.statuses, (statuses) => { columns.value = JSON.parse(JSON.stringify(statuses)); }, { immediate: true, deep: true });

const changed = (event, column) => {
    const task = event.added?.element || event.moved?.element;
    if (!task) return;
    const oldStatus = task.status_id;
    task.status_id = column.id;
    router.patch(route('tasks.move', task.id), { status_id: column.id, order: event.added?.newIndex ?? event.moved?.newIndex ?? 0, ordered_ids: column.tasks.map((item) => item.id) }, {
        preserveScroll: true, preserveState: true, only: ['statuses', 'flash', 'notifications'],
        onError: () => { task.status_id = oldStatus; router.reload({ only: ['statuses'] }); },
    });
};
const closeMenus = (event) => {
    if (event?.type === 'keydown' && event.key !== 'Escape') return;
    board.value?.querySelectorAll('details[open]').forEach((menu) => {
        if (!event || event.key === 'Escape' || !menu.contains(event.target)) menu.removeAttribute('open');
    });
};
const startRename = (column) => {
    editingId.value = column.id;
    editingName.value = column.name;
    nextTick(() => editInput.value?.focus());
};
const cancelRename = () => { editingId.value = null; editingName.value = ''; };
const saveRename = (column) => {
    const name = editingName.value.trim();
    if (!name) return;
    const previous = column.name;
    column.name = name;
    cancelRename();
    router.patch(route('statuses.update', column.id), { name }, { preserveScroll: true, onError: () => { column.name = previous; } });
};
const remove = (column) => { closeMenus(); if (confirm(`Delete “${column.name}”?`)) router.delete(route('statuses.destroy', column.id), { preserveScroll: true }); };
onMounted(() => { document.addEventListener('pointerdown', closeMenus); document.addEventListener('keydown', closeMenus); });
onBeforeUnmount(() => { document.removeEventListener('pointerdown', closeMenus); document.removeEventListener('keydown', closeMenus); });
</script>

<template>
    <div v-if="columns.length" ref="board" class="kanban-scroll flex gap-4 overflow-x-auto pb-2"
        :class="contained ? 'h-full min-h-0' : 'min-h-[460px]'">
        <section v-for="column in columns" :key="column.id"
            class="w-[300px] shrink-0 rounded-2xl bg-slate-100/70 p-2.5 sm:w-[320px]"
            :class="contained && 'flex h-full min-h-0 flex-col'">
            <header class="relative mb-3 flex min-h-9 items-center justify-between px-1">
                <div v-if="editingId === column.id" class="relative z-10 w-full pb-9">
                    <input :ref="setEditInput" v-model="editingName"
                        class="h-10 w-full rounded-lg border-2 border-blue-500 bg-white px-3 text-sm font-semibold text-slate-900 outline-none ring-2 ring-blue-200"
                        maxlength="255" @keydown.enter.prevent="saveRename(column)"
                        @keydown.esc.prevent="cancelRename" />
                    <div
                        class="absolute right-0 top-11 flex gap-1 rounded-lg border border-slate-200 bg-white p-1 shadow-lg">
                        <button class="grid h-7 w-8 place-items-center rounded-md text-emerald-600 hover:bg-emerald-50"
                            aria-label="Save column name" @click="saveRename(column)">
                            <Check class="h-4 w-4" />
                        </button>
                        <button class="grid h-7 w-8 place-items-center rounded-md text-slate-500 hover:bg-slate-100"
                            aria-label="Cancel editing" @click="cancelRename">
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>
                <div v-else class="flex min-w-0 items-center gap-2"><span class="h-2.5 w-2.5 shrink-0 rounded-full"
                        :style="{ backgroundColor: column.color }" />
                    <button class="truncate text-left text-xs font-bold text-slate-700 hover:text-blue-600"
                        title="Click to rename" @click="startRename(column)">{{ column.name }}</button><span
                        class="rounded-full bg-white px-2 py-0.5 text-[10px] font-bold text-slate-400 shadow-sm">{{
                            column.tasks.length }}</span>
                </div>
                <details v-if="editingId !== column.id" class="relative">
                    <summary class="list-none cursor-pointer rounded-lg p-1.5 text-slate-400 hover:bg-white">
                        <MoreVertical class="h-4 w-4" />
                    </summary>
                    <div
                        class="absolute right-0 z-20 mt-1 w-32 rounded-xl border border-slate-200 bg-white p-1 text-xs shadow-xl">
                        <button class="w-full rounded-lg px-3 py-2 text-left text-red-600 hover:bg-red-50"
                            @click="remove(column)">Delete</button>
                    </div>
                </details>
            </header>
            <draggable v-model="column.tasks" group="tasks" item-key="id" class="min-h-10 space-y-2.5"
                :class="contained && 'flex-1 overflow-y-auto pr-1'" ghost-class="drag-ghost" :animation="180"
                @change="changed($event, column)"><template #item="{ element }">
                    <TaskCard :task="element" @open="$emit('open-task', $event)" />
                </template>
            </draggable>
            <button
                class="mt-2.5 flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-dashed border-slate-300 bg-white/70 text-xs font-semibold text-slate-500 transition hover:border-blue-300 hover:text-blue-600"
                @click="$emit('add-task', column.id)">
                <Plus class="h-4 w-4" />Add task
            </button>
        </section>
        <button
            class="flex h-12 w-40 shrink-0 items-center justify-center gap-2 rounded-xl border border-dashed border-slate-300 text-xs font-semibold text-slate-500 hover:border-blue-300 hover:text-blue-600"
            @click="$emit('add-column')">
            <Plus class="h-4 w-4" />Add column
        </button>
    </div>
    <slot v-else name="empty" />
</template>
