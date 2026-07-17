<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import { usePermissions } from '@/composables/usePermissions';
import type { WorkspaceSummary } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Check, CreditCard, KeyRound, ShieldCheck, UserRound } from 'lucide-vue-next';

const props = defineProps<{ tab: string; workspace: WorkspaceSummary | null }>();
const { currentUser: settingsUser } = usePermissions();
const tabs = [
    { id: 'profile', label: 'Profile', icon: UserRound },
    { id: 'security', label: 'Security', icon: ShieldCheck },
];
const profile = useForm({
    _method: 'patch',
    name: settingsUser.value.name,
    email: settingsUser.value.email,
    job_title: settingsUser.value.job_title || '',
    phone: settingsUser.value.phone || '',
    avatar: null as File | null,
});
const workspaceForm = useForm({ name: props.workspace?.name || '' });
const preferences = useForm({
    email_task_assigned: settingsUser.value.notification_preferences?.email_task_assigned ?? true,
    email_due_reminder: settingsUser.value.notification_preferences?.email_due_reminder ?? true,
    push_comments: settingsUser.value.notification_preferences?.push_comments ?? true,
});
const password = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});
const preferenceItems: {
    key: 'email_task_assigned' | 'email_due_reminder' | 'push_comments';
    label: string;
    desc: string;
}[] = [
    {
        key: 'email_task_assigned',
        label: 'Task assignments',
        desc: 'Email me when someone assigns a task.',
    },
    {
        key: 'email_due_reminder',
        label: 'Due date reminders',
        desc: 'Email me before assigned work is due.',
    },
    {
        key: 'push_comments',
        label: 'Comments and mentions',
        desc: 'Show in-app alerts for comments and mentions.',
    },
];
const saveProfile = () =>
    profile.post(route('profile.update'), {
        forceFormData: true,
        preserveScroll: true,
    });
const savePassword = () =>
    password.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => password.reset(),
    });
</script>

<template>
    <Head title="Settings" />
    <AppLayout title="Settings" section="Account">
        <div class="page-shell max-w-6xl">
            <div>
                <h1 class="text-2xl font-bold">Settings</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Manage your account and workspace preferences.
                </p>
            </div>
            <div class="mt-6 grid gap-5 lg:grid-cols-[220px_1fr]">
                <aside class="ui-card h-fit p-2">
                    <Link
                        v-for="item in tabs"
                        :key="item.id"
                        :href="route('settings', item.id)"
                        class="flex h-11 items-center gap-3 rounded-xl px-3 text-sm font-semibold"
                        :class="
                            tab === item.id
                                ? 'bg-blue-50 text-blue-700'
                                : 'text-slate-500 hover:bg-slate-50'
                        "
                    >
                        <component :is="item.icon" class="h-4 w-4" />{{ item.label }}
                    </Link>
                </aside>
                <section class="ui-card p-5 sm:p-7">
                    <form
                        v-if="tab === 'profile'"
                        class="max-w-2xl space-y-5"
                        @submit.prevent="saveProfile"
                    >
                        <div>
                            <h2 class="text-lg font-bold">Profile details</h2>
                            <p class="text-sm text-slate-400">
                                Your personal details and public team profile.
                            </p>
                        </div>
                        <div class="flex items-center gap-4">
                            <img
                                :src="
                                    settingsUser.avatar ||
                                    `https://ui-avatars.com/api/?name=${encodeURIComponent(profile.name)}`
                                "
                                class="h-20 w-20 rounded-2xl object-cover"
                            /><label class="ui-button-secondary cursor-pointer"
                                >Change photo<input
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    @change="
                                        profile.avatar =
                                            ($event.target as HTMLInputElement).files?.[0] ?? null
                                    "
                            /></label>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="ui-label">Full name</label
                                ><input v-model="profile.name" class="ui-input" />
                                <InputError :message="profile.errors.name" />
                            </div>
                            <div>
                                <label class="ui-label">Email</label
                                ><input v-model="profile.email" type="email" class="ui-input" />
                                <InputError :message="profile.errors.email" />
                            </div>
                            <div>
                                <label class="ui-label">Job title</label
                                ><input v-model="profile.job_title" class="ui-input" />
                            </div>
                            <div>
                                <label class="ui-label">Phone</label
                                ><input v-model="profile.phone" class="ui-input" />
                            </div>
                        </div>
                        <button class="ui-button-primary" :disabled="profile.processing">
                            Save profile
                        </button>
                    </form>
                    <form
                        v-else-if="tab === 'workspace'"
                        class="max-w-2xl space-y-5"
                        @submit.prevent="workspaceForm.patch(route('settings.workspace'))"
                    >
                        <div>
                            <h2 class="text-lg font-bold">Workspace</h2>
                            <p class="text-sm text-slate-400">
                                Update workspace identity and defaults.
                            </p>
                        </div>
                        <div>
                            <label class="ui-label">Workspace name</label
                            ><input v-model="workspaceForm.name" class="ui-input" />
                        </div>
                        <div>
                            <label class="ui-label">Workspace ID</label
                            ><input
                                :value="workspace?.id"
                                class="ui-input bg-slate-50 text-slate-400"
                                disabled
                            />
                        </div>
                        <button class="ui-button-primary">Save workspace</button>
                    </form>
                    <form
                        v-else-if="tab === 'notifications'"
                        class="max-w-2xl"
                        @submit.prevent="preferences.patch(route('settings.preferences'))"
                    >
                        <h2 class="text-lg font-bold">Notification preferences</h2>
                        <p class="text-sm text-slate-400">Choose which updates should reach you.</p>
                        <div class="mt-6 divide-y divide-slate-100">
                            <label
                                v-for="item in preferenceItems"
                                :key="item.key"
                                class="flex items-center justify-between gap-5 py-4"
                                ><span
                                    ><strong class="block text-sm">{{ item.label }}</strong
                                    ><span class="mt-1 block text-xs text-slate-400">{{
                                        item.desc
                                    }}</span></span
                                ><input
                                    v-model="preferences[item.key]"
                                    type="checkbox"
                                    class="h-5 w-5 rounded border-slate-300 text-blue-600"
                            /></label>
                        </div>
                        <button class="ui-button-primary mt-5">Save preferences</button>
                    </form>
                    <div v-else-if="tab === 'billing'" class="max-w-2xl">
                        <h2 class="text-lg font-bold">Billing plan</h2>
                        <p class="text-sm text-slate-400">Your current workspace subscription.</p>
                        <div
                            class="mt-6 rounded-2xl bg-gradient-to-br from-slate-950 to-blue-950 p-6 text-white"
                        >
                            <div class="flex justify-between">
                                <span class="rounded-lg bg-white/10 px-2 py-1 text-xs font-bold"
                                    >PRO</span
                                >
                                <CreditCard class="h-5 w-5 text-blue-300" />
                            </div>
                            <p class="mt-8 text-3xl font-bold">
                                $24
                                <span class="text-sm font-normal text-slate-400"
                                    >/ member / month</span
                                >
                            </p>
                            <p class="mt-2 text-sm text-slate-300">
                                Unlimited projects, automations, reporting, and priority support.
                            </p>
                            <ul class="mt-5 space-y-2 text-sm text-slate-200">
                                <li class="flex gap-2">
                                    <Check class="h-4 w-4 text-emerald-400" />All Lacakeen features
                                </li>
                                <li class="flex gap-2">
                                    <Check class="h-4 w-4 text-emerald-400" />Unlimited file storage
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div v-else class="max-w-2xl">
                        <h2 class="text-lg font-bold">Security</h2>
                        <p class="text-sm text-slate-400">Password and account sessions.</p>
                        <form
                            class="mt-6 space-y-4 rounded-xl border border-slate-200 p-5"
                            @submit.prevent="savePassword"
                        >
                            <h3 class="flex items-center gap-2 text-sm font-bold">
                                <KeyRound class="h-4 w-4 text-blue-600" />Change password
                            </h3>
                            <div>
                                <label class="ui-label">Current password</label
                                ><input
                                    v-model="password.current_password"
                                    type="password"
                                    class="ui-input"
                                />
                                <InputError :message="password.errors.current_password" />
                            </div>
                            <div>
                                <label class="ui-label">New password</label
                                ><input
                                    v-model="password.password"
                                    type="password"
                                    class="ui-input"
                                />
                                <InputError :message="password.errors.password" />
                            </div>
                            <div>
                                <label class="ui-label">Confirm password</label
                                ><input
                                    v-model="password.password_confirmation"
                                    type="password"
                                    class="ui-input"
                                />
                            </div>
                            <button class="ui-button-primary">Update password</button>
                        </form>
                        <div class="mt-4 rounded-xl border border-slate-200 p-5">
                            <h3 class="text-sm font-bold">Active sessions</h3>
                            <p class="mt-2 text-sm text-slate-500">
                                This browser ·
                                {{ Intl.DateTimeFormat().resolvedOptions().timeZone }}
                            </p>
                            <button class="mt-4 text-sm font-semibold text-red-600">
                                Log out other devices
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
