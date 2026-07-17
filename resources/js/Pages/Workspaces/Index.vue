<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import AppSelect from '@/Components/ui/AppSelect.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Modal from '@/Components/ui/Modal.vue';
import type { WorkspaceListItem } from '@/types/workspace';
import type { TaskAssignee } from '@/types/task';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight, Building2, FolderKanban, Plus, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{ workspaces: WorkspaceListItem[]; availableOwners: TaskAssignee[] }>();
const createOpen = ref(false);
const create = useForm({ name: '', owner_id: '' as number | '' });
const ownerOptions = computed(() =>
    props.availableOwners.map((owner) => ({
        value: owner.id,
        label: `${owner.name} (${owner.email})`,
    }))
);

const submitCreate = () =>
    create.post(route('workspaces.store'), {
        onSuccess: () => {
            createOpen.value = false;
            create.reset();
        },
    });
</script>

<template>
    <Head title="Workspaces" />
    <AppLayout title="Workspaces" section="Tools">
        <div class="page-shell">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <h1 class="text-2xl font-bold">Workspaces</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Every division in the company, and who owns it.
                    </p>
                </div>
                <button class="ui-button-primary" @click="createOpen = true">
                    <Plus class="h-4 w-4" />New workspace
                </button>
            </div>

            <div v-if="workspaces.length" class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Link
                    v-for="workspace in workspaces"
                    :key="workspace.id"
                    :href="route('workspaces.show', workspace.id)"
                    class="ui-card group block p-5 transition hover:-translate-y-0.5 hover:shadow-md"
                >
                    <div class="flex items-start justify-between">
                        <div
                            class="grid h-11 w-11 place-items-center rounded-xl bg-blue-600 text-white"
                        >
                            <Building2 class="h-5 w-5" />
                        </div>
                        <ArrowRight class="h-4 w-4 text-slate-300" />
                    </div>
                    <h2 class="mt-5 font-bold group-hover:text-blue-700">{{ workspace.name }}</h2>
                    <p class="mt-1 text-xs text-slate-400">Owned by {{ workspace.owner.name }}</p>
                    <div
                        class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4 text-xs font-semibold text-slate-400"
                    >
                        <span class="flex items-center gap-1"
                            ><Users class="h-3.5 w-3.5" />{{
                                workspace.members_count
                            }}
                            members</span
                        >
                        <span class="flex items-center gap-1"
                            ><FolderKanban class="h-3.5 w-3.5" />{{
                                workspace.projects_count
                            }}
                            projects</span
                        >
                    </div>
                </Link>
            </div>
            <EmptyState
                v-else
                class="mt-4"
                title="No workspaces yet"
                description="Create the first workspace to represent a division."
                ><button class="ui-button-primary" @click="createOpen = true">
                    New workspace
                </button></EmptyState
            >
        </div>

        <Modal :open="createOpen" title="Create workspace" @close="createOpen = false"
            ><form class="space-y-4" @submit.prevent="submitCreate">
                <div>
                    <label class="ui-label">Workspace name</label
                    ><input v-model="create.name" class="ui-input" required />
                </div>
                <div>
                    <label class="ui-label">Owner</label
                    ><AppSelect
                        v-model="create.owner_id"
                        :options="ownerOptions"
                        placeholder="Select an owner"
                    />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" class="ui-button-secondary" @click="createOpen = false">
                        Cancel</button
                    ><button class="ui-button-primary" :disabled="create.processing">
                        Create workspace
                    </button>
                </div>
            </form></Modal
        >
    </AppLayout>
</template>
