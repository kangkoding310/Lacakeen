<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AvatarStack from '@/Components/ui/AvatarStack.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Modal from '@/Components/ui/Modal.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Archive, ArchiveRestore, ArrowRight, FolderKanban, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({ projects: Array, workspace: Object });
const page = usePage();
const createOpen = ref(false);
const editOpen = ref(false);
const filter = ref('active');
const create = useForm({ name: '', prefix: '', description: '', color: '#2563EB', workspace_id: props.workspace?.id || '' });
const edit = useForm({ name: '', prefix: '', description: '', color: '#2563EB' });
const editingProject = ref(null);
const filteredProjects = computed(() => props.projects.filter((project) => project.status === filter.value));
const projectRole = (project) => project.members.find((member) => member.id === page.props.auth.user.id)?.pivot?.role_in_project;
const canManage = (project) => page.props.auth.user.roles.some((role) => role.name === 'admin') || ['owner', 'editor'].includes(projectRole(project));
const canDelete = (project) => page.props.auth.user.roles.some((role) => role.name === 'admin') || projectRole(project) === 'owner';
const canCreate = computed(() => page.props.auth.user.roles.some((role) => role.name === 'admin') || props.workspace?.owner_id === page.props.auth.user.id);

const submitCreate = () => create.post(route('projects.store'), { onSuccess: () => { createOpen.value = false; create.reset(); } });
const startEdit = (project) => {
    editingProject.value = project;
    edit.defaults({ name: project.name, prefix: project.prefix, description: project.description || '', color: project.color });
    edit.reset();
    editOpen.value = true;
};
const submitEdit = () => edit.patch(route('projects.update', editingProject.value.id), { preserveScroll: true, onSuccess: () => { editOpen.value = false; } });
const toggleArchive = (project) => router.patch(route('projects.update', project.id), { status: project.status === 'active' ? 'archived' : 'active' }, { preserveScroll: true });
const remove = (project) => { if (confirm(`Permanently delete “${project.name}” and all its tasks?`)) router.delete(route('projects.destroy', project.id)); };
</script>

<template>
    <Head title="Projects" />
    <AppLayout title="Projects" section="Dashboard">
        <div class="page-shell">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div><h1 class="text-2xl font-bold">Projects</h1><p class="mt-1 text-sm text-slate-500">Create, organize, archive, and manage project access.</p></div>
                <button v-if="canCreate" class="ui-button-primary" @click="createOpen = true"><Plus class="h-4 w-4" />New project</button>
            </div>

            <div class="mt-6 inline-flex rounded-xl bg-slate-100 p-1">
                <button v-for="status in ['active', 'archived']" :key="status" class="rounded-lg px-4 py-2 text-xs font-semibold capitalize" :class="filter === status ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500'" @click="filter = status">{{ status }} <span class="ml-1 text-slate-400">{{ projects.filter(project => project.status === status).length }}</span></button>
            </div>

            <div v-if="filteredProjects.length" class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <article v-for="project in filteredProjects" :key="project.id" class="ui-card group p-5 transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex items-start justify-between"><div class="grid h-11 w-11 place-items-center rounded-xl text-white" :style="{ backgroundColor: project.color }"><FolderKanban class="h-5 w-5" /></div><div v-if="canManage(project)" class="flex gap-1"><button class="ui-icon-button h-8 w-8" title="Edit project" @click="startEdit(project)"><Pencil class="h-3.5 w-3.5" /></button><button class="ui-icon-button h-8 w-8" :title="project.status === 'active' ? 'Archive project' : 'Restore project'" @click="toggleArchive(project)"><Archive v-if="project.status === 'active'" class="h-3.5 w-3.5" /><ArchiveRestore v-else class="h-3.5 w-3.5" /></button><button v-if="canDelete(project)" class="ui-icon-button h-8 w-8 text-red-500" title="Delete project" @click="remove(project)"><Trash2 class="h-3.5 w-3.5" /></button></div></div>
                    <Link :href="route('projects.show', project.id)" class="block"><h2 class="mt-5 flex items-center justify-between font-bold group-hover:text-blue-700">{{ project.name }}<ArrowRight class="h-4 w-4 text-slate-300" /></h2><p class="mt-1 text-xs font-semibold text-slate-400">{{ project.prefix }}</p><p class="mt-2 line-clamp-2 min-h-10 text-sm leading-5 text-slate-500">{{ project.description }}</p><div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4"><AvatarStack :users="project.members" /><span class="text-xs font-semibold text-slate-400">{{ project.tasks_count }} tasks</span></div></Link>
                </article>
            </div>
            <EmptyState v-else class="mt-4" :title="filter === 'active' ? 'No active projects' : 'No archived projects'" :description="filter === 'active' ? 'Create a project to start organizing work.' : 'Archived projects will appear here.'"><button v-if="filter === 'active' && canCreate" class="ui-button-primary" @click="createOpen = true">New project</button></EmptyState>
        </div>

        <Modal :open="createOpen" title="Create project" @close="createOpen = false"><form class="space-y-4" @submit.prevent="submitCreate"><div><label class="ui-label">Project name</label><input v-model="create.name" class="ui-input" required /></div><div class="grid grid-cols-[1fr_80px] gap-3"><div><label class="ui-label">Prefix</label><input v-model="create.prefix" class="ui-input uppercase" maxlength="12" placeholder="WEB" required /></div><div><label class="ui-label">Color</label><input v-model="create.color" type="color" class="ui-input p-1" /></div></div><div><label class="ui-label">Description</label><textarea v-model="create.description" class="ui-input min-h-24 py-2" /></div><div class="flex justify-end gap-2"><button type="button" class="ui-button-secondary" @click="createOpen = false">Cancel</button><button class="ui-button-primary" :disabled="create.processing">Create project</button></div></form></Modal>
        <Modal :open="editOpen" title="Edit project" @close="editOpen = false"><form class="space-y-4" @submit.prevent="submitEdit"><div><label class="ui-label">Project name</label><input v-model="edit.name" class="ui-input" required /></div><div class="grid grid-cols-[1fr_80px] gap-3"><div><label class="ui-label">Prefix</label><input v-model="edit.prefix" class="ui-input uppercase" maxlength="12" required /></div><div><label class="ui-label">Color</label><input v-model="edit.color" type="color" class="ui-input p-1" /></div></div><div><label class="ui-label">Description</label><textarea v-model="edit.description" class="ui-input min-h-24 py-2" /></div><div class="flex justify-end gap-2"><button type="button" class="ui-button-secondary" @click="editOpen = false">Cancel</button><button class="ui-button-primary" :disabled="edit.processing">Save changes</button></div></form></Modal>
    </AppLayout>
</template>
