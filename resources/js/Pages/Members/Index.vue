<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppSelect from '@/Components/ui/AppSelect.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Modal from '@/Components/ui/Modal.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Activity,
    BriefcaseBusiness,
    CheckCircle2,
    Mail,
    MoreHorizontal,
    Plus,
    Search,
    UserRoundCheck,
} from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({ members: Object, roles: Array, filters: Object });
const inviteOpen = ref(false);
const selected = ref(null);
const query = useForm({
    search: props.filters.search || '',
    role: props.filters.role || '',
    status: props.filters.status || '',
});
const invite = useForm({ email: '', role: 'member' });
const roleOptions = props.roles.map((role) => ({ value: role, label: role.replace('_', ' ') }));
const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];
const apply = () =>
    router.get(route('members'), query.data(), { preserveState: true, replace: true });
const submitInvite = () =>
    invite.post(route('members.invite'), {
        preserveScroll: true,
        onSuccess: () => {
            inviteOpen.value = false;
            invite.reset();
        },
    });
const update = (member, data) =>
    router.patch(route('members.update', member.id), data, { preserveScroll: true });
const completion = (member) => {
    const total = member.active_tasks_count + member.completed_tasks_count;
    return total ? Math.round((member.completed_tasks_count / total) * 100) : 0;
};
</script>

<template>
    <Head title="Members" />
    <AppLayout title="Members" section="Tools">
        <div class="page-shell">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <h1 class="text-2xl font-bold">Team members</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Manage access, roles, and workload across your workspace.
                    </p>
                </div>
                <button
                    v-if="$page.props.auth.user.roles.some((role) => role.name === 'admin')"
                    class="ui-button-primary"
                    @click="inviteOpen = true"
                >
                    <Plus class="h-4 w-4" />Invite member
                </button>
            </div>
            <form
                class="ui-card mt-6 grid gap-3 p-3 sm:grid-cols-[1fr_180px_180px_auto]"
                @submit.prevent="apply"
            >
                <div class="relative">
                    <Search
                        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    /><input
                        v-model="query.search"
                        class="ui-input pl-9"
                        placeholder="Search name or email"
                    />
                </div>
                <AppSelect v-model="query.role" :options="roleOptions" placeholder="All roles" />
                <AppSelect
                    v-model="query.status"
                    :options="statusOptions"
                    placeholder="All statuses"
                    :searchable="false"
                /><button class="ui-button-secondary">Apply filters</button>
            </form>
            <div class="table-shell mt-4 overflow-x-auto">
                <table class="w-full min-w-[840px] text-left">
                    <thead
                        class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-400"
                    >
                        <tr>
                            <th class="px-5 py-3">Member</th>
                            <th class="px-3 py-3">Role</th>
                            <th class="px-3 py-3">Active tasks</th>
                            <th class="px-3 py-3">Completion</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3">Joined</th>
                            <th class="w-16"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="member in members.data"
                            :key="member.id"
                            class="cursor-pointer text-sm hover:bg-slate-50"
                            @click="selected = member"
                        >
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <img
                                        :src="
                                            member.avatar ||
                                            `https://ui-avatars.com/api/?name=${encodeURIComponent(member.name)}`
                                        "
                                        class="h-10 w-10 rounded-xl object-cover"
                                    />
                                    <div>
                                        <p class="font-bold text-slate-800">{{ member.name }}</p>
                                        <p class="text-xs text-slate-400">{{ member.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-4">
                                <span
                                    class="rounded-lg bg-violet-50 px-2.5 py-1 text-xs font-semibold capitalize text-violet-700"
                                    >{{
                                        member.roles[0]?.name?.replace('_', ' ') || 'member'
                                    }}</span
                                >
                            </td>
                            <td class="px-3 py-4 font-bold text-slate-700">
                                {{ member.active_tasks_count }}
                            </td>
                            <td class="px-3 py-4">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-1.5 w-20 overflow-hidden rounded-full bg-slate-100"
                                    >
                                        <div
                                            class="h-full rounded-full bg-emerald-500"
                                            :style="{ width: `${completion(member)}%` }"
                                        />
                                    </div>
                                    <span class="text-xs text-slate-500"
                                        >{{ completion(member) }}%</span
                                    >
                                </div>
                            </td>
                            <td class="px-3 py-4">
                                <span
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold"
                                    :class="
                                        member.status === 'active'
                                            ? 'text-emerald-600'
                                            : 'text-slate-400'
                                    "
                                    ><span
                                        class="h-2 w-2 rounded-full"
                                        :class="
                                            member.status === 'active'
                                                ? 'bg-emerald-500'
                                                : 'bg-slate-300'
                                        "
                                    />{{ member.status }}</span
                                >
                            </td>
                            <td class="px-3 py-4 text-xs text-slate-400">
                                {{ new Date(member.created_at).toLocaleDateString() }}
                            </td>
                            <td class="px-3 py-4">
                                <MoreHorizontal class="h-4 w-4 text-slate-400" />
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="!members.data.length" class="p-5">
                    <EmptyState title="No members found" />
                </div>
            </div>
            <div v-if="members.links?.length > 3" class="mt-4 flex justify-end gap-1">
                <Link
                    v-for="link in members.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    v-html="link.label"
                    class="grid min-h-9 min-w-9 place-items-center rounded-lg border border-slate-200 bg-white px-3 text-xs"
                    :class="link.active && 'bg-blue-600 text-white'"
                />
            </div>
        </div>
        <Modal
            :open="inviteOpen"
            title="Invite a teammate"
            description="They will receive a secure invitation by email."
            @close="inviteOpen = false"
        >
            <form class="space-y-4" @submit.prevent="submitInvite">
                <div>
                    <label class="ui-label">Email address</label>
                    <input
                        v-model="invite.email"
                        type="email"
                        class="ui-input"
                        placeholder="teammate@company.com"
                        required
                    />
                </div>
                <div>
                    <label class="ui-label">Workspace role</label>
                    <AppSelect v-model="invite.role" :options="roleOptions" :can-clear="false" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" class="ui-button-secondary" @click="inviteOpen = false">
                        Cancel
                    </button>
                    <button class="ui-button-primary" :disabled="invite.processing">
                        <Mail class="h-4 w-4" />Send invitation
                    </button>
                </div>
            </form>
        </Modal>
        <Modal
            :open="!!selected"
            :title="selected?.name"
            :description="selected?.email"
            wide
            @close="selected = null"
        >
            <div v-if="selected" class="grid gap-6 sm:grid-cols-[180px_1fr]">
                <aside>
                    <img
                        :src="
                            selected.avatar ||
                            `https://ui-avatars.com/api/?name=${encodeURIComponent(selected?.name)}`
                        "
                        class="h-24 w-24 rounded-2xl object-cover"
                    />
                    <p class="mt-3 flex items-center gap-2 text-sm text-slate-500">
                        <BriefcaseBusiness class="h-4 w-4" />{{ selected.job_title }}
                    </p>
                    <div class="mt-5 grid grid-cols-2 gap-2">
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-xl font-bold">{{ selected.active_tasks_count }}</p>
                            <p class="text-[10px] text-slate-400">active tasks</p>
                        </div>
                        <div class="rounded-xl bg-emerald-50 p-3">
                            <p class="text-xl font-bold text-emerald-700">
                                {{ completion(selected) }}%
                            </p>
                            <p class="text-[10px] text-emerald-600">completed</p>
                        </div>
                    </div>
                    <div
                        v-if="$page.props.auth.user.roles.some((role) => role.name === 'admin')"
                        class="mt-5 space-y-2"
                    >
                        <AppSelect
                            :model-value="selected.roles[0]?.name"
                            :options="roleOptions"
                            :can-clear="false"
                            @update:model-value="update(selected, { role: $event })"
                        /><button
                            class="ui-button-secondary w-full"
                            @click="
                                update(selected, {
                                    status: selected.status === 'active' ? 'inactive' : 'active',
                                })
                            "
                        >
                            {{ selected.status === 'active' ? 'Deactivate user' : 'Activate user' }}
                        </button>
                    </div>
                </aside>
                <section>
                    <h3 class="flex items-center gap-2 font-bold">
                        <Activity class="h-4 w-4 text-blue-600" />Assigned work
                    </h3>
                    <div class="mt-3 space-y-2">
                        <Link
                            v-for="task in selected.assigned_tasks"
                            :key="task.id"
                            :href="route('tasks.show', task.id)"
                            class="flex items-center justify-between rounded-xl border border-slate-200 p-3 hover:bg-slate-50"
                        >
                            <div>
                                <p class="text-xs font-bold text-blue-600">{{ task.code }}</p>
                                <p class="mt-1 text-sm font-semibold">{{ task.title }}</p>
                            </div>
                            <span class="rounded-lg bg-slate-100 px-2 py-1 text-xs">{{
                                task.status.name
                            }}</span>
                        </Link>
                        <p
                            v-if="!selected.assigned_tasks.length"
                            class="rounded-xl bg-slate-50 p-5 text-center text-sm text-slate-400"
                        >
                            No assigned tasks.
                        </p>
                    </div>
                </section>
            </div>
        </Modal>
    </AppLayout>
</template>
