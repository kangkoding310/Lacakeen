<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import AppSelect from '@/Components/ui/AppSelect.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Modal from '@/Components/ui/Modal.vue';
import { workflowService } from '@/services/workflowService';
import type { WorkflowItem } from '@/types/workflow';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowRight, CheckCircle2, GitBranch, Plus, Sparkles, Zap } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface WorkflowProjectOption {
    id: string;
    name: string;
}

const props = defineProps<{ workflows: WorkflowItem[]; projects: WorkflowProjectOption[] }>();
const open = ref(false);
const form = useForm({
    project_id: '',
    name: '',
    description: '',
    trigger_type: 'status_changed',
    trigger_value: 'Completed',
    action_type: 'notify',
    action_target: 'project_manager',
});
const projectOptions = computed(() =>
    props.projects.map((project) => ({ value: project.id, label: project.name }))
);
const triggerOptions = [
    { value: 'task_created', label: 'Task created' },
    { value: 'status_changed', label: 'Status changed' },
    { value: 'due_date_near', label: 'Due date near' },
];
const actionOptions = [
    { value: 'notify', label: 'Send notification' },
    { value: 'assign', label: 'Assign user' },
    { value: 'change_label', label: 'Change label' },
    { value: 'email', label: 'Send email' },
];
const submit = () =>
    form.post(route('workflow.store'), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    });
const toggle = (workflow: WorkflowItem) =>
    workflowService.toggle(workflow.id, !workflow.is_active, { preserveScroll: true });
const label = (value?: string | null) =>
    value?.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
</script>
<template>
    <Head title="Workflow" />
    <AppLayout title="Workflow"
        ><div class="page-shell">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <h1 class="text-2xl font-bold">Workflow automation</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Let Lacakeen handle repetitive project updates.
                    </p>
                </div>
                <button class="ui-button-primary" @click="open = true">
                    <Plus class="h-4 w-4" />New workflow
                </button>
            </div>
            <div v-if="workflows.length" class="mt-6 grid gap-4 lg:grid-cols-2">
                <article v-for="workflow in workflows" :key="workflow.id" class="ui-card p-5">
                    <div class="flex items-start justify-between">
                        <div class="flex gap-3">
                            <div
                                class="grid h-10 w-10 place-items-center rounded-xl bg-violet-50 text-violet-600"
                            >
                                <Zap class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="font-bold text-slate-900">{{ workflow.name }}</p>
                                <p class="mt-1 text-xs text-slate-400">
                                    {{ workflow.project.name }}
                                </p>
                            </div>
                        </div>
                        <button
                            class="relative h-6 w-11 rounded-full transition"
                            :class="workflow.is_active ? 'bg-blue-600' : 'bg-slate-200'"
                            @click="toggle(workflow)"
                        >
                            <span
                                class="absolute top-1 h-4 w-4 rounded-full bg-white shadow transition"
                                :class="workflow.is_active ? 'left-6' : 'left-1'"
                            />
                        </button>
                    </div>
                    <p class="mt-4 text-sm leading-6 text-slate-500">{{ workflow.description }}</p>
                    <div class="mt-5 flex items-center gap-2 rounded-xl bg-slate-50 p-3 text-xs">
                        <span
                            class="rounded-lg bg-white px-2.5 py-2 font-semibold text-slate-700 shadow-sm"
                            >When: {{ label(workflow.trigger.type) }}
                            {{ workflow.trigger.value }}</span
                        ><ArrowRight class="h-4 w-4 shrink-0 text-slate-300" /><span
                            class="rounded-lg bg-blue-50 px-2.5 py-2 font-semibold text-blue-700"
                            >Then: {{ label(workflow.actions[0].type) }}
                            {{ label(workflow.actions[0].target) }}</span
                        >
                    </div>
                    <div class="mt-4 flex items-center justify-between text-xs">
                        <span
                            class="flex items-center gap-2"
                            :class="workflow.is_active ? 'text-emerald-600' : 'text-slate-400'"
                            ><CheckCircle2 class="h-4 w-4" />{{
                                workflow.is_active ? 'Active and monitoring events' : 'Paused'
                            }}</span
                        ><span class="text-slate-400"
                            >{{ workflow.logs_count }} runs<span v-if="workflow.logs[0]">
                                · last
                                {{
                                    new Date(workflow.logs[0].created_at).toLocaleDateString()
                                }}</span
                            ></span
                        >
                    </div>
                </article>
            </div>
            <EmptyState
                v-else
                class="mt-6"
                title="No automations yet"
                description="Create a trigger and action to automate routine work."
                ><button class="ui-button-primary" @click="open = true">
                    Create workflow
                </button></EmptyState
            >
        </div>
        <Modal
            :open="open"
            title="Build an automation"
            description="Choose one trigger and one action to get started."
            @close="open = false"
            ><form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label class="ui-label">Workflow name</label
                    ><input
                        v-model="form.name"
                        class="ui-input"
                        required
                        placeholder="Notify PM when work completes"
                    />
                </div>
                <div>
                    <label class="ui-label">Project</label
                    ><AppSelect
                        v-model="form.project_id"
                        :options="projectOptions"
                        placeholder="Select a project"
                        :can-clear="false"
                    />
                </div>
                <div>
                    <label class="ui-label">Description</label
                    ><textarea v-model="form.description" class="ui-input min-h-20 py-2" />
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="mb-3 flex items-center gap-2 text-sm font-bold">
                        <GitBranch class="h-4 w-4 text-violet-600" />Trigger
                    </p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <AppSelect
                            v-model="form.trigger_type"
                            :options="triggerOptions"
                            :searchable="false"
                            :can-clear="false"
                        /><input
                            v-model="form.trigger_value"
                            class="ui-input"
                            placeholder="Completed"
                        />
                    </div>
                </div>
                <div class="rounded-xl border border-blue-100 bg-blue-50/40 p-4">
                    <p class="mb-3 flex items-center gap-2 text-sm font-bold">
                        <Sparkles class="h-4 w-4 text-blue-600" />Action
                    </p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <AppSelect
                            v-model="form.action_type"
                            :options="actionOptions"
                            :searchable="false"
                            :can-clear="false"
                        /><input
                            v-model="form.action_target"
                            class="ui-input"
                            placeholder="project_manager"
                        />
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" class="ui-button-secondary" @click="open = false">
                        Cancel</button
                    ><button class="ui-button-primary" :disabled="form.processing">
                        Create automation
                    </button>
                </div>
            </form></Modal
        >
    </AppLayout>
</template>
