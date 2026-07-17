<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import AppSelect from '@/Components/ui/AppSelect.vue';
import Modal from '@/Components/ui/Modal.vue';
import { usePermissions } from '@/composables/usePermissions';
import { workspaceService } from '@/services/workspaceService';
import type { WorkspaceDetail, WorkspaceMember, WorkspaceRole } from '@/types/workspace';
import type { TaskAssignee } from '@/types/task';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Building2,
    FolderKanban,
    Pencil,
    ShieldCheck,
    Trash2,
    UserPlus,
    Users,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{ workspace: WorkspaceDetail; availableMembers: TaskAssignee[] }>();
const { isAdmin, canManageWorkspace, canDeleteWorkspace } = usePermissions();
const editOpen = ref(false);
const addMemberOpen = ref(false);
const edit = useForm({ name: props.workspace.name });
const addMember = useForm({ user_id: '' as number | '', role: 'member' as WorkspaceRole });

const canManage = computed(() => canManageWorkspace(props.workspace));
const canDelete = computed(() => canDeleteWorkspace(props.workspace));
const memberOptions = computed(() =>
    props.availableMembers.map((member) => ({
        value: member.id,
        label: `${member.name} (${member.email})`,
    }))
);

const startEdit = () => {
    edit.defaults({ name: props.workspace.name });
    edit.reset();
    editOpen.value = true;
};
const submitEdit = () =>
    edit.patch(route('workspaces.update', props.workspace.id), {
        preserveScroll: true,
        onSuccess: () => {
            editOpen.value = false;
        },
    });
const submitAddMember = () =>
    addMember.post(route('workspaces.members.store', props.workspace.id), {
        preserveScroll: true,
        onSuccess: () => {
            addMemberOpen.value = false;
            addMember.reset();
        },
    });
const updateRole = (member: WorkspaceMember, role: WorkspaceRole) =>
    workspaceService.updateMember(
        props.workspace.id,
        member.id,
        { role },
        { preserveScroll: true }
    );
const removeMember = (member: WorkspaceMember) => {
    if (confirm(`Remove “${member.name}” from this workspace?`))
        workspaceService.removeMember(props.workspace.id, member.id, { preserveScroll: true });
};
const removeWorkspace = () => {
    if (
        confirm(
            `Permanently delete “${props.workspace.name}” and all its projects? This cannot be undone.`
        )
    )
        workspaceService.destroy(props.workspace.id);
};
</script>

<template>
    <Head title="Workspace" />
    <AppLayout title="Workspace" section="Tools">
        <div class="page-shell max-w-4xl">
            <Link
                v-if="isAdmin"
                :href="route('workspaces.index')"
                class="mb-4 flex w-fit items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-blue-700"
            >
                <ArrowLeft class="h-3.5 w-3.5" />All workspaces
            </Link>
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <h1 class="text-2xl font-bold">Workspace</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Manage your workspace identity and who has access to it.
                    </p>
                </div>
                <button v-if="canManage" class="ui-button-secondary" @click="startEdit">
                    <Pencil class="h-4 w-4" />Edit workspace
                </button>
            </div>

            <div class="ui-card mt-6 p-5 sm:p-6">
                <div class="flex items-center gap-4">
                    <div
                        class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-blue-600 text-white"
                    >
                        <Building2 class="h-6 w-6" />
                    </div>
                    <div class="min-w-0">
                        <h2 class="truncate text-lg font-bold">{{ workspace.name }}</h2>
                        <p class="text-xs text-slate-400">
                            Created {{ new Date(workspace.created_at).toLocaleDateString() }}
                        </p>
                    </div>
                </div>
                <div class="mt-5 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-2">
                    <div class="flex items-center gap-3">
                        <img
                            :src="
                                workspace.owner.avatar ||
                                `https://ui-avatars.com/api/?name=${encodeURIComponent(workspace.owner.name)}`
                            "
                            class="h-9 w-9 rounded-lg object-cover"
                        />
                        <div class="min-w-0">
                            <p
                                class="text-[11px] font-semibold uppercase tracking-wide text-slate-400"
                            >
                                Owner
                            </p>
                            <p class="truncate text-sm font-semibold">{{ workspace.owner.name }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="grid h-9 w-9 place-items-center rounded-lg bg-slate-100">
                            <FolderKanban class="h-4 w-4 text-slate-500" />
                        </div>
                        <div>
                            <p
                                class="text-[11px] font-semibold uppercase tracking-wide text-slate-400"
                            >
                                Projects
                            </p>
                            <p class="text-sm font-semibold">{{ workspace.projects_count }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-700">
                    <Users class="h-4 w-4" />Members
                </h2>
                <button v-if="canManage" class="ui-button-secondary" @click="addMemberOpen = true">
                    <UserPlus class="h-4 w-4" />Add member
                </button>
            </div>
            <div class="ui-card mt-3 divide-y divide-slate-100">
                <div class="flex items-center justify-between gap-3 px-5 py-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <img
                            :src="
                                workspace.owner.avatar ||
                                `https://ui-avatars.com/api/?name=${encodeURIComponent(workspace.owner.name)}`
                            "
                            class="h-8 w-8 rounded-full object-cover"
                        />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">{{ workspace.owner.name }}</p>
                            <p class="truncate text-xs text-slate-400">
                                {{ workspace.owner.email }}
                            </p>
                        </div>
                    </div>
                    <span
                        class="flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-bold text-blue-700"
                    >
                        <ShieldCheck class="h-3 w-3" />Owner
                    </span>
                </div>
                <div
                    v-for="member in workspace.members"
                    :key="member.id"
                    class="flex items-center justify-between gap-3 px-5 py-3"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <img
                            :src="
                                member.avatar ||
                                `https://ui-avatars.com/api/?name=${encodeURIComponent(member.name)}`
                            "
                            class="h-8 w-8 rounded-full object-cover"
                        />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">{{ member.name }}</p>
                            <p class="truncate text-xs text-slate-400">{{ member.email }}</p>
                        </div>
                    </div>
                    <div v-if="canManage" class="flex items-center gap-2">
                        <select
                            class="ui-input h-9 w-28 py-0 text-xs"
                            :value="member.pivot.role"
                            @change="
                                updateRole(
                                    member,
                                    ($event.target as HTMLSelectElement).value as WorkspaceRole
                                )
                            "
                        >
                            <option value="member">Member</option>
                            <option value="admin">Admin</option>
                        </select>
                        <button
                            class="ui-icon-button h-8 w-8 text-red-500"
                            title="Remove member"
                            @click="removeMember(member)"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </button>
                    </div>
                    <span v-else class="text-xs font-semibold capitalize text-slate-400">{{
                        member.pivot.role
                    }}</span>
                </div>
                <p v-if="!workspace.members.length" class="px-5 py-4 text-sm text-slate-400">
                    No additional members yet.
                </p>
            </div>

            <div v-if="canDelete" class="ui-card mt-8 border-red-100 p-5 sm:p-6">
                <h2 class="text-sm font-bold text-red-600">Danger zone</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Deleting this workspace permanently removes it and all of its projects.
                </p>
                <button class="ui-button-secondary mt-4 text-red-600" @click="removeWorkspace">
                    <Trash2 class="h-4 w-4" />Delete workspace
                </button>
            </div>
        </div>

        <Modal :open="editOpen" title="Edit workspace" @close="editOpen = false"
            ><form class="space-y-4" @submit.prevent="submitEdit">
                <div>
                    <label class="ui-label">Workspace name</label
                    ><input v-model="edit.name" class="ui-input" required />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" class="ui-button-secondary" @click="editOpen = false">
                        Cancel</button
                    ><button class="ui-button-primary" :disabled="edit.processing">
                        Save changes
                    </button>
                </div>
            </form></Modal
        >
        <Modal :open="addMemberOpen" title="Add member" @close="addMemberOpen = false"
            ><form class="space-y-4" @submit.prevent="submitAddMember">
                <div>
                    <label class="ui-label">Member</label
                    ><AppSelect
                        v-model="addMember.user_id"
                        :options="memberOptions"
                        placeholder="Select a user"
                    />
                </div>
                <div>
                    <label class="ui-label">Role</label
                    ><select v-model="addMember.role" class="ui-input">
                        <option value="member">Member</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        class="ui-button-secondary"
                        @click="addMemberOpen = false"
                    >
                        Cancel</button
                    ><button class="ui-button-primary" :disabled="addMember.processing">
                        Add member
                    </button>
                </div>
            </form></Modal
        >
    </AppLayout>
</template>
